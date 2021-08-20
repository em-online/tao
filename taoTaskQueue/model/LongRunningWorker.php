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

use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\QueueInterface;
use oat\tao\model\taskQueue\QueuerInterface;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\tao\model\taskQueue\Worker\AbstractWorker;

/**
 * Processes tasks from the queue service running for limited/unlimited time
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
final class LongRunningWorker extends AbstractWorker
{
    const WAIT_INTERVAL = 1; // sec
    const MAX_SLEEPING_TIME_FOR_DEDICATED_QUEUE = 30; //max sleeping time if working on only one queue

    private $maxIterations = 0; //0 means infinite iteration
    private $iterations = 0;
    private $shutdown;
    private $paused;
    private $iterationsWithOutTask = 0;
    private $handleSignals;

    public function __construct(QueuerInterface $queuer, TaskLogInterface $taskLog, $handleSignals = true)
    {
        parent::__construct($queuer, $taskLog);

        $this->handleSignals = $handleSignals;

        if ($handleSignals) {
            $this->registerSigHandlers();
        }
    }

    protected function getLogContext()
    {
        $rs = [
            'PID' => getmypid(),
            'Iteration' => $this->iterations
        ];

        if ($this->queuer instanceof QueueInterface) {
            $rs['QueueName'] = $this->queuer->getName();
        }

        return $rs;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->logDebug('Starting LongRunningWorker.', $this->getLogContext());

        while ($this->isRunning()) {

            if($this->paused) {
                $this->logDebug('Paused... ', $this->getLogContext());
                usleep(self::WAIT_INTERVAL * 1000000);
                continue;
            }

            ++$this->iterations;

            try{
                $this->logDebug('Fetching tasks from queue ', $this->getLogContext());

                $task = $this->queuer->dequeue();

                // if no task to process, sleep for the specified time and continue.
                if (!$task) {
                    ++$this->iterationsWithOutTask;
                    $waitInterval = $this->getWaitInterval();
                    $this->logDebug('Sleeping for '. $waitInterval .' sec', $this->getLogContext());
                    usleep($waitInterval * 1000000);

                    continue;
                }

                // we have task, so set this back to 0
                $this->iterationsWithOutTask = 0;

                if (!$task instanceof TaskInterface) {
                    $this->logWarning('The received queue item ('. $task .') not processable.', $this->getLogContext());
                    continue;
                }

                $this->processTask($task);

                unset($task);
            } catch (\Exception $e) {
                $this->logError('Fetching data from queue failed with MSG: '. $e->getMessage(), $this->getLogContext());
                continue;
            }
        }

        $this->logDebug('LongRunningWorker finished.', $this->getLogContext());
    }

    /**
     * @inheritdoc
     */
    public function setMaxIterations($maxIterations)
    {
        if (!$this->queuer instanceof QueueInterface) {
            throw new \LogicException('Limit can be set only if a dedicated queue is set.');
        }

        $this->maxIterations = $maxIterations * $this->queuer->getNumberOfTasksToReceive();

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
            $this->logError('Please make sure that "pcntl" is enabled.', $this->getLogContext());
            throw new \RuntimeException('Please make sure that "pcntl" is enabled.');
        }

        declare(ticks = 1);

        pcntl_signal(SIGTERM, array($this, 'shutdown'));
        pcntl_signal(SIGINT, array($this, 'shutdown'));
        pcntl_signal(SIGQUIT, array($this, 'shutdown'));
        pcntl_signal(SIGUSR2, array($this, 'pauseProcessing'));
        pcntl_signal(SIGCONT, array($this, 'unPauseProcessing'));

        $this->logDebug('Finished setting up signal handlers', $this->getLogContext());
    }

    public function shutdown()
    {
        $this->logDebug('TERM/INT/QUIT received; shutting down gracefully...', $this->getLogContext());
        $this->shutdown = true;
    }

    public function pauseProcessing()
    {
        $this->logDebug('USR2 received; pausing task processing...', $this->getLogContext());
        $this->paused = true;
    }

    public function unPauseProcessing()
    {
        $this->logDebug('CONT received; resuming task processing...', $this->getLogContext());
        $this->paused = false;
    }

    /**
     * Calculate the sleeping time dynamically in case of no task to work on.
     *
     * @return int (sec)
     */
    private function getWaitInterval()
    {
        if ($this->queuer instanceof QueueInterface) {
            $waitTime = $this->iterationsWithOutTask * self::WAIT_INTERVAL;

            return min($waitTime, self::MAX_SLEEPING_TIME_FOR_DEDICATED_QUEUE);
        } else if ($this->queuer instanceof QueueDispatcherInterface) {
            return (int) $this->queuer->getWaitTime();
        } else {
            return self::WAIT_INTERVAL;
        }
    }
}
