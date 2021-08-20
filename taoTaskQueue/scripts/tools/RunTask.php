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
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\tao\model\taskQueue\Worker\OneTimeTask;
use common_report_Report as Report;

/**
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\RunTask' -t <some_task_serialized_and_base64encoded>
 */

class RunTask extends ScriptAction
{
    /**
     * @return Report
     * @throws \Exception
     */
    protected function run()
    {
        /** @var QueueDispatcherInterface $queueService */
        $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);

        /** @var TaskLogInterface $taskLog */
        $taskLog = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);
        /** @var TaskSerializerService $taskSerializer */
        $taskSerializer =  $this->getServiceLocator()->get(TaskSerializerService::SERVICE_ID);

        $taskJSON = $this->getOption('task');
        $task = $taskSerializer->deserialize(base64_decode($taskJSON));

        $runner = new OneTimeTask($queueService, $taskLog);
        $runner->setTask($task);

        $status = $runner->run();

        return Report::createInfo($status);
    }

    protected function provideOptions()
    {
        return [
            'task' => [
                'prefix'      => 't',
                'longPrefix'  => 'task',
                'cast'        => 'string',
                'required'    => true,
                'description' => 'Task json-base64encoded to run.'
            ]
        ];
    }

    protected function provideDescription()
    {
        return 'Run a task';
    }
}