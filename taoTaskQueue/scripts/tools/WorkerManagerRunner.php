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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoTaskQueue\scripts\tools;

use oat\oatbox\extension\script\ScriptAction;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\TaskSerializerService;
use oat\taoTaskQueue\model\Worker\WorkerProcessManager;
use React\EventLoop\Factory;

class WorkerManagerRunner extends ScriptAction
{
    /**
     * Run Script.
     *
     * Run the userland script. Implementers will use this method
     * to implement the main logic of the script.
     *
     * @return \common_report_Report
     * @throws \Exception
     */
    protected function run()
    {
        if (stripos(PHP_OS, 'win') === 0) {
           throw new \Exception('The WorkerManagerRunner only works on linux');
        }

        /** @var WorkerProcessManager $workerManager */
        $workerManager = $this->getServiceLocator()->get(WorkerProcessManager::SERVICE_ID);
        $workerManager->setLimitOfCpu($this->getOption('limitOfCpu'));
        $workerManager->setLimitOfMemory($this->getOption('limitOfMemory'));

        /** @var TaskSerializerService $taskSerializer */
        $taskSerializer = $this->getServiceLocator()->get(TaskSerializerService::SERVICE_ID);
        $queueService   = $this->getQueueService();
        $loop           = Factory::create();

        $interval = $this->getOption('interval');
        $loop->addPeriodicTimer($interval, function () use ($loop, $queueService, $taskSerializer, $workerManager) {
            if ($workerManager->canRun()) {
                $task = $queueService->dequeue();
                if ($task !== null) {
                    $cmd = $workerManager->getCommand();
                    $taskJson = base64_encode($taskSerializer->serialize($task));
                    $cmd = 'cd '.ROOT_PATH.' && '.$cmd.' -t '.$taskJson;

                    $process = new \React\ChildProcess\Process($cmd);
                    $process->start($loop);
                    $workerManager->addProcess($process);
                }

                unset($task);
            }
        });

        $loop->run();
    }

    /**
     * @return \common_report_Report|QueueDispatcherInterface
     * @throws \Exception
     */
    protected function getQueueService()
    {
        /** @var QueueDispatcherInterface $queueService */
        $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);

        if ($queueService->isSync()) {
            throw new \Exception('No worker is needed because all registered queue is a Sync Queue.');
        }

        return $queueService;
    }

    protected function provideOptions()
    {
        return [
            'interval' => [
                'prefix'      => 'i',
                'longPrefix'  => 'interval',
                'cast'        => 'integer',
                'required'    => true,
                'description' => 'The interval to run a task.'
            ],
            'limitOfCpu' => [
                'prefix'      => 'cpu',
                'longPrefix'  => 'limitOfCpu',
                'cast'        => 'integer',
                'required'    => true,
                'description' => 'CPU Limit percentage'
            ],
            'limitOfMemory' => [
                'prefix'      => 'm',
                'longPrefix'  => 'limitOfMemory',
                'cast'        => 'integer',
                'required'    => true,
                'description' => 'Memory limit percentage'
            ]
        ];
    }

    /**
     * @return string
     */
    protected function provideDescription()
    {
        return 'Run tasks as a new fork process';
    }
}