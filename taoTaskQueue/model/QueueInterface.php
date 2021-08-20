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

use oat\oatbox\PhpSerializable;
use oat\tao\model\taskQueue\Queue\Broker\QueueBrokerInterface;
use oat\taoTaskQueue\model\Task\TaskInterface;
use Psr\Log\LoggerAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Interface QueueInterface
 *
 * @deprecated Use \oat\tao\model\taskQueue\QueueInterface
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
interface QueueInterface extends \Countable, LoggerAwareInterface, PhpSerializable, ServiceLocatorAwareInterface
{
    /**
     * @deprecated It will be removed in version 1.0.0. Use QueueDispatcherInterface::SERVICE_ID instead
     */
    const SERVICE_ID = 'taoTaskQueue/taskQueue';

    /**
     * @deprecated It will be removed in version 1.0.0
     */
    const OPTION_QUEUE_BROKER = 'queue_broker';

    /**
     * QueueInterface constructor.
     *
     * @param string               $name
     * @param QueueBrokerInterface $broker
     * @param int                  $weight
     */
    public function __construct($name, QueueBrokerInterface $broker, $weight = 1);

    /**
     * @return string
     */
    public function __toString();

    /**
     * Initialize queue.
     *
     * @return void
     */
    public function initialize();

    /**
     * Returns queue name.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns queue weight.
     *
     * @return int
     */
    public function getWeight();

    /**
     * @param int $weight
     * @return QueueInterface
     */
    public function setWeight($weight);

    /**
     * Publish a task to the queue.
     *
     * @param TaskInterface $task
     * @param null|string   $label Label for the task
     * @return bool Is the task successfully enqueued?
     */
    public function enqueue(TaskInterface $task, $label = null);

    /**
     * Receive a task from the queue.
     *
     * @return null|TaskInterface
     */
    public function dequeue();

    /**
     * Acknowledge that the task has been received and consumed.
     *
     * @param TaskInterface $task
     */
    public function acknowledge(TaskInterface $task);

    /**
     * Is the given queue a sync queue?
     *
     * @return bool
     */
    public function isSync();

    /**
     * The amount of tasks that can be received in one pop by this queue.
     *
     * @return int
     */
    public function getNumberOfTasksToReceive();

    /**
     * Set new broker.
     *
     * @param QueueBrokerInterface $broker
     * @return QueueInterface
     */
    public function setBroker(QueueBrokerInterface $broker);

    /**
     * @return QueueBrokerInterface
     */
    public function getBroker();
}