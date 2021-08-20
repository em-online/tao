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

use common_report_Report as Report;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use oat\oatbox\action\Action;

/**
 * Class AddTaskToQueue
 *
 * Action to add task to the task queue.
 *
 * Run example:
 * ```
 * sudo -u www-data php index.php '\oat\taoTaskQueue\scripts\tools\AddTaskToQueue' '\Task\To\Be\Run' param1 param2
 * ```
 *
 * First parameter is the task class including namespace. Should be instance of oat\oatbox\action\Action interface
 * Further parameters are parameters to be passed to the task's __invoke() function at time of launch.
 *
 * @package oat\taoTaskQueue\scripts\tools
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class AddTaskToQueue implements Action, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    public function __invoke($params)
    {
        $action = array_shift($params);

        if (!class_exists($action)) {
            return Report::createFailure('Action class does not exist');
        }

        if (!is_subclass_of($action, Action::class)) {
            return Report::createFailure('Action in not instance of ' . Action::class);
        }

        $actionInstance = new $action;
        /** @var QueueDispatcherInterface $queueDispatcher */
        $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        $queueDispatcher->createTask($actionInstance, $params, $action);
        return Report::createInfo('Task ' . $action . ' has been successfully added to the queue');
    }
}
