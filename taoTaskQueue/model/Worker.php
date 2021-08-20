<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoTaskQueue\model;

use oat\oatbox\log\LoggerAwareTrait;
use common_report_Report as Report;
use oat\taoTaskQueue\model\Entity\TaskLogEntity;
use oat\taoTaskQueue\model\Task\CallbackTaskInterface;
use oat\taoTaskQueue\model\Task\RemoteTaskSynchroniserInterface;
use oat\taoTaskQueue\model\Task\TaskInterface;
use oat\taoTaskQueue\model\ValueObjects\TaskLogCategorizedStatus;

/**
 * Processes tasks from the queue.
 *
 * @deprecated Use \oat\taoTaskQueue\model\LongRunningWorker
 *             
 * @author Gyula Szucs <gyula@taotesting.com>
 */
final class Worker implements WorkerInterface
{
    use LoggerAwareTrait;

    const WAIT_INTERVAL = 1; // sec
    const MAX_SLEEPING_TIME_FOR_DEDICATED_QUEUE = 30; //max sleeping time if working on only one queue

    /**
     * @var QueueDispatcherInterface
     */
    private $queueService;

    /**
     * @var QueueInterface
     */
    private $dedicatedQueue;

    private $maxIterations = 0; //0 means infinite iteration
    private $iterations;
    private $shutdown;
    private $paused;
    private $iterationsWithOutTask = 0;
    private $processId;
    private $logContext;
    private $taskLog;
    /**
     * @var bool
     */
    private $handleSignals;

    /**
     * @param QueueDispatcherInterface $queueService
     * @param TaskLogInterface         $taskLog
     * @param bool                     $handleSignals
     */
    public function __construct(QueueDispatcherInterface $queueService, TaskLogInterface $taskLog, $handleSignals = true)
    {
        $this->queueService = $queueService;
        $this->taskLog = $taskLog;
        $this->handleSignals = $handleSignals;
        $this->processId = getmypid();

        $this->logContext = [
            'PID' => $this->processId
        ];

        if ($handleSignals) {
            $this->registerSigHandlers();
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->logDebug('Starting worker.', $this->logContext);

        while ($this->isRunning()) {

            if($this->paused) {
                $this->logDebug('Paused... ', array_merge($this->logContext, [
                    'Iteration' => $this->iterations
                ]));
                usleep(self::WAIT_INTERVAL * 1000000);
                continue;
            }

            ++$this->iterations;

            $this->logContext = array_merge($this->logContext, [
                'Iteration' => $this->iterations
            ]);

            try{
                $this->logDebug('Fetching tasks from queue ', $this->logContext);

                // if there is a dedicated queue set, let's do dequeue on that one
                // otherwise using the built-in strategy to get a new task from any registered queue
                $task = $this->dedicatedQueue instanceof QueueInterface
                    ? $this->dedicatedQueue->dequeue()
                    : $this->queueService->dequeue();

                // if no task to process, sleep for the specified time and continue.
                if (!$task) {
                    ++$this->iterationsWithOutTask;
                    $waitInterval = $this->getWaitInterval();
                    $this->logDebug('Sleeping for '. $waitInterval .' sec', $this->logContext);
                    usleep($waitInterval * 1000000);

                    continue;
                }

                // we have task, so set this back to 0
                $this->iterationsWithOutTask = 0;

                if (!$task instanceof TaskInterface) {
                    $this->logWarning('The received queue item ('. $task .') not processable.', $this->logContext);
                    continue;
                }

                $this->processTask($task);

                unset($task);
            } catch (\Exception $e) {
                $this->logError('Fetching data from queue failed with MSG: '. $e->getMessage(), $this->logContext);
                continue;
            }
        }

        $this->logDebug('Worker finished.', $this->logContext);
    }

    /**
     * @inheritdoc
     */
    public function processTask(TaskInterface $task)
    {
        $report = Report::createInfo(__('Running task %s', $task->getId()));

        try {
            $this->logDebug('Processing task '. $task->getId(), $this->logContext);

            $rowsTouched = $this->taskLog->setStatus($task->getId(), TaskLogInterface::STATUS_RUNNING, TaskLogInterface::STATUS_DEQUEUED);

            // if the task is being executed by another worker, just return, no report needs to be saved
            if (!$rowsTouched) {
                $this->logDebug('Task '. $task->getId() .' seems to be processed by another worker.', $this->logContext);
                return TaskLogInterface::STATUS_UNKNOWN;
            }

            // let the task know that it is called from a worker
            $task->applyWorkerContext();

            // execute the task
            $taskReport = $task();

            if (!$taskReport instanceof Report) {
                $this->logWarning('Task '. $task->getId() .' should return a report object.', $this->logContext);
                $taskReport = Report::createInfo(__('Task not returned any report.'));
            }

            $report->add($taskReport);
            unset($taskReport, $rowsTouched);
        } catch (\Exception $e) {
            $this->logError('Executing task '. $task->getId() .' failed with MSG: '. $e->getMessage(), $this->logContext);
            $report = Report::createFailure(__('Executing task %s failed', $task->getId()));
        }

        // Initial status
        $status = $report->getType() == Report::TYPE_ERROR || $report->containsError()
            ? TaskLogInterface::STATUS_FAILED
            : TaskLogInterface::STATUS_COMPLETED;

        // Change the status if the task has children
        if ($task->hasChildren() && $status == TaskLogInterface::STATUS_COMPLETED) {
            $status = TaskLogInterface::STATUS_CHILD_RUNNING;
        }

        $cloneCreated = false;

        // if the task is a special sync task: the status of the parent task depends on the status of the remote task.
        if ($this->isRemoteTaskSynchroniser($task) && $status == TaskLogInterface::STATUS_COMPLETED) {
            // if the remote task is still in progress, we have to reschedule this task
            // the RESTApi returns TaskLogCategorizedStatus values
            if (in_array($this->getRemoteStatus($task), [TaskLogCategorizedStatus::STATUS_CREATED, TaskLogCategorizedStatus::STATUS_IN_PROGRESS])) {
                if ($this->queueService->count() <= 1) {
                    //if there is less than or exactly one task in the queue, let's sleep a bit, in order not to regenerate the same task too much
                    sleep(3);
                }

                $cloneCreated = $this->queueService->enqueue(clone $task, $task->getLabel());
            } elseif ($this->getRemoteStatus($task) == TaskLogCategorizedStatus::STATUS_FAILED) {
                // if the remote task status is failed
                $status = TaskLogInterface::STATUS_FAILED;
            }
        }

        if (!$cloneCreated) {
            $this->taskLog->setReport($task->getId(), $report, $status);
        } else {
            // if there is a clone, delete the old task log
            //TODO: once we have the centralized way of cleaning up the log table, this should be refactored
            $this->taskLog->getBroker()->deleteById($task->getId());
        }

        // Update parent
        if ($task->hasParent()) {
            /** @var TaskLogEntity $parentLogTask */
            $parentLogTask = $this->taskLog->getById($task->getParentId());
            if (!$parentLogTask->isMasterStatus()) {
                $this->taskLog->updateParent($task->getParentId());
            }

        }

        unset($report);

        // delete message from queue
        $this->queueService->acknowledge($task);

        return $status;
    }

    /**
     * @param TaskInterface $task
     * @return bool
     */
    private function isRemoteTaskSynchroniser(TaskInterface $task)
    {
        return $task instanceof RemoteTaskSynchroniserInterface || ($task instanceof CallbackTaskInterface && $task->getCallable() instanceof RemoteTaskSynchroniserInterface);
    }

    /**
     * @param TaskInterface $task
     * @return mixed
     */
    private function getRemoteStatus(TaskInterface $task)
    {
        return $task instanceof CallbackTaskInterface ? $task->getCallable()->getRemoteStatus() : $task->getRemoteStatus();
    }

    /**
     * Only set-able if there is a dedicated queue set.
     * @deprecated
     *
     * @inheritdoc
     */
    public function setMaxIterations($maxIterations)
    {
        $this->maxIterations = $maxIterations;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDedicatedQueue(QueueInterface $queue, $maxIterations = 0)
    {
        $this->dedicatedQueue = $queue;
        $this->maxIterations  = (int) $maxIterations * $this->dedicatedQueue->getNumberOfTasksToReceive();

        $this->logContext['QueueName'] = $queue->getName();

        return $this;
    }

    /**
     * @return bool
     */
    private function isRunning()
    {
        if ($this->handleSignals) {
            pcntl_signal_dispatch();
        }

        if ($this->shutdown) {
            return false;
        }

        if ($this->maxIterations > 0) {
            return $this->iterations < $this->maxIterations;
        }

        return true;
    }

    /**
     * Register signal handlers that a worker should respond to.
     *
     * TERM/INT/QUIT: Shutdown after the current job is finished then exit.
     * USR2: Pause worker, no new jobs will be processed but the current one will be finished.
     * CONT: Resume worker.
     */
    private function registerSigHandlers()
    {
        if (!function_exists('pcntl_signal')) {
            $this->logError('Please make sure that "pcntl" is enabled.', $this->logContext);
            throw new \RuntimeException('Please make sure that "pcntl" is enabled.');
        }

        declare(ticks = 1);

        pcntl_signal(SIGTERM, array($this, 'shutdown'));
        pcntl_signal(SIGINT, array($this, 'shutdown'));
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));
        pcntl_signal(SIGUSR2, array($this, 'pauseProcessing'));
        pcntl_signal(SIGCONT, array($this, 'unPauseProcessing'));

        $this->logDebug('Finished setting up signal handlers', $this->logContext);
    }

    public function shutdown()
    {
        $this->logDebug('TERM/INT/QUIT received; shutting down gracefully...', $this->logContext);
        $this->shutdown = true;
    }

    public function pauseProcessing()
    {
        $this->logDebug('USR2 received; pausing task processing...', $this->logContext);
        $this->paused = true;
    }

    public function unPauseProcessing()
    {
        $this->logDebug('CONT received; resuming task processing...', $this->logContext);
        $this->paused = false;
    }

    /**
     * Calculate the sleeping time dynamically in case of no task to work on.
     *
     * @return int (sec)
     */
    private function getWaitInterval()
    {
        if ($this->dedicatedQueue instanceof QueueInterface) {
            $waitTime = $this->iterationsWithOutTask * self::WAIT_INTERVAL;

            return min($waitTime, self::MAX_SLEEPING_TIME_FOR_DEDICATED_QUEUE);
        } else {
            return (int) $this->queueService->getWaitTime();
        }
    }
}
