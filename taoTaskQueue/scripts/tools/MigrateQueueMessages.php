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

namespace oat\taoTaskQueue\scripts\tools;

use oat\oatbox\action\Action;
use oat\oatbox\task\Task;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\Taskqueue\JsonTask;
use oat\Taskqueue\Persistence\RdsQueue;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\MigrateQueueMessages'
 * ```
 */

class MigrateQueueMessages implements Action, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    public function __invoke($params)
    {
        try {
            $count = 0;

            /** @var RdsQueue $oldRdsQueue */
            $oldRdsQueue = $this->getServiceLocator()->get(RdsQueue::SERVICE_ID);
            /** @var QueueDispatcher $queueService */
            $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);

            /** @var JsonTask $queueItem */
            foreach ($oldRdsQueue as $queueItem)
            {
                $label = $queueItem->getLabel();
                $invokeString = $queueItem->getInvocable();
                $parameters = $queueItem->getParameters();

                $queueService->setOwner($queueItem->getOwner());
                $queueService->createTask(new $invokeString, $parameters, $label);

                $oldRdsQueue->updateTaskStatus($queueItem->getId(), Task::STATUS_ARCHIVED);
                $count++;
            }

            return \common_report_Report::createSuccess('Imported with success: '. $count);

        } catch (\Exception $exception) {

            $message = $exception->getMessage();
            return \common_report_Report::createFailure($message);
        }
    }
}