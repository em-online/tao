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

use oat\tao\model\taskQueue\Queue\TaskSelector\SelectorStrategyInterface;
use oat\taoTaskQueue\model\Task\CallbackTaskInterface;
use oat\taoTaskQueue\model\Task\TaskInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Interface QueueDispatcherInterface
 *
 * @deprecated Use \oat\tao\model\taskQueue\QueueDispatcherInterface
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
interface QueueDispatcherInterface extends \Countable, LoggerAwareInterface
{
    /** @deprecated  */
    const SERVICE_ID = 'taoTaskQueue/taskQueue';

    const FILE_SYSTEM_ID = 'taskQueueStorage';

    /**
     * Array of queues
     */
    const OPTION_QUEUES = 'queues';

    /**
     * Name of the default queue. Task without specified queue will be published here.
     */
    const OPTION_DEFAULT_QUEUE = 'default_queue';

    /**
     * An array of tasks names with the specified queue where the tasks needs to be published to.
     */
    const OPTION_TASK_TO_QUEUE_ASSOCIATIONS = 'task_to_queue_associations';

    const OPTION_TASK_LOG = 'task_log';

    const OPTION_TASK_SELECTOR_STRATEGY = 'task_selector_strategy';

    const QUEUE_PREFIX = 'TQ';

    /**
     * Add new Queue.
     *
     * @param QueueInterface $queue
     * @return QueueDispatcherInterface
     */
    public function addQueue(QueueInterface $queue);

    /**
     * @param QueueInterface[] $queues
     * @return QueueDispatcherInterface
     */
    public function setQueues(array $queues);

    /**
     * @param string $queueName
     * @return QueueInterface
     */
    public function getQueue($queueName);

    /**
     * @return QueueInterface[]
     */
    public function getQueues();

    /**
     * Get the names of the registered queues.
     *
     * @return array
     */
    public function getQueueNames();

    /**
     * Has the given queue/queue name already been set?
     *
     * @param string $queueName
     * @return bool
     */
    public function hasQueue($queueName);

    /**
     * Get the default queue.
     *
     * @return QueueInterface
     */
    public function getDefaultQueue();

    /**
     * Get a queue randomly using weight.
     *
     * @deprecated
     * @return QueueInterface
     */
    public function getQueueByWeight();

    /**
     * Link a task to a queue.
     *
     * @param string|object $taskName
     * @param string $queueName
     * @return QueueDispatcherInterface
     */
    public function linkTaskToQueue($taskName, $queueName);

    /**
     * Get the linked tasks.
     *
     * @return array
     */
    public function getLinkedTasks();

    /**
     * Initialize queues.
     *
     * @return void
     */
    public function initialize();

    /**
     * Create a task to be managed by the queue from any callable
     *
     * @param callable    $callable
     * @param array       $parameters
     * @param null|string $label Label for the task
     * @param null|TaskInterface $parent
     * @param boolean $masterStatus
     * @return CallbackTaskInterface
     */
    public function createTask(callable $callable, array $parameters = [], $label = null, TaskInterface $parent = null, $masterStatus = false);

    /**
     * Publish a task to a queue.
     *
     * @param TaskInterface $task
     * @param null|string   $label Label for the task
     * @return bool Is the task successfully enqueued?
     */
    public function enqueue(TaskInterface $task, $label = null);

    /**
     * Receive a task from a specified queue or from a queue selected by a predefined strategy
     *
     * @param null|string $queueName
     * @return null|TaskInterface
     */
    public function dequeue($queueName = null);

    /**
     * Acknowledge that the task has been received and consumed.
     *
     * @param TaskInterface $task
     */
    public function acknowledge(TaskInterface $task);

    /**
     * Are all queues a sync one?
     *
     * @return bool
     */
    public function isSync();

    /**
     * Get resource from rdf storage which represents task in the task queue by linked resource
     * Returns null if there is no task linked to given resource
     *
     * It will be deprecated once we have the general GUI for displaying different info of a task for the user.
     *
     * @param \core_kernel_classes_Resource $resource
     * @return null|\core_kernel_classes_Resource
     */
    public function getTaskResource(\core_kernel_classes_Resource $resource);

    /**
     * Get report by a linked resource.
     *
     * It will be deprecated once we have the general GUI for displaying different info of a task for the user.
     *
     * @param \core_kernel_classes_Resource $resource
     * @return \common_report_Report
     */
    public function getReportByLinkedResource(\core_kernel_classes_Resource $resource);

    /**
     * Create task resource in the rdf storage and link placeholder resource to it.
     *
     * It will be deprecated once we have the general GUI for displaying different info of a task for the user.
     *
     * @param TaskInterface                      $task
     * @param \core_kernel_classes_Resource|null $resource Placeholder resource linked to the task
     */
    public function linkTaskToResource(TaskInterface $task, \core_kernel_classes_Resource $resource = null);

    /**
     * @param SelectorStrategyInterface $selectorStrategy
     * @return QueueDispatcherInterface
     */
    public function setTaskSelector(SelectorStrategyInterface $selectorStrategy);

    /**
     * Seconds for the worker to wait if there is no task.
     *
     * @return int
     */
    public function getWaitTime();
}