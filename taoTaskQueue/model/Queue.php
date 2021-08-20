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
use oat\tao\model\taskQueue\Queue\Broker\QueueBrokerInterface;
use oat\taoTaskQueue\model\QueueBroker\SyncQueueBrokerInterface;
use oat\taoTaskQueue\model\Task\TaskInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Queue Service
 *
 * @deprecated Use \oat\tao\model\taskQueue\Queue
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class Queue implements QueueInterface, TaskLogAwareInterface
{
    use LoggerAwareTrait;
    use ServiceLocatorAwareTrait;
    use TaskLogAwareTrait;

    private $name;

    /**
     * @var QueueBrokerInterface|ServiceLocatorAwareInterface
     */
    private $broker;

    /**
     * @var int
     */
    private $weight;

    /**
     * Queue constructor.
     *
     * @param string              $name
     * @param QueueBrokerInterface|null $broker Null option will be removed in version 1.0.0
     * @param int $weight
     */
    public function __construct($name, QueueBrokerInterface $broker = null, $weight = 1)
    {
        /**
         * this "if case" is because of backwards compatibility, will be removed in version 1.0.0
         *
         * @deprecated
         */
        if (is_array($name)) {
            $oldConfig = $name;
            $name = $oldConfig['queue_name'];
            $broker = $oldConfig['queue_broker'];
        }

        if (empty($name)) {
            throw new \InvalidArgumentException("Queue name needs to be set.");
        }

        if (!$broker instanceof QueueBrokerInterface) {
            throw new \InvalidArgumentException("Queue Broker needs to be an instance of QueueBrokerInterface.");
        }

        $this->name = $name;
        $this->setWeight($weight);

        $this->setBroker($broker);
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @inheritdoc
     */
    public function __toPhpCode()
    {
        return 'new '. get_called_class() .'('
            . \common_Utils::toHumanReadablePhpString($this->getName())
            .', '
            . \common_Utils::toHumanReadablePhpString($this->getBroker())
            .', '
            . \common_Utils::toHumanReadablePhpString($this->getWeight())
            .')';
    }

    /**
     * @inheritdoc
     */
    public function initialize()
    {
        $this->getBroker()->createQueue();
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $weight
     * @return Queue
     */
    public function setWeight($weight)
    {
        $this->weight = abs($weight);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @inheritdoc
     */
    public function setBroker(QueueBrokerInterface $broker)
    {
        $this->broker = $broker;

        $this->broker->setQueueName($this->getName());

        return $this;
    }

    /**
     * Returns the queue broker service.
     *
     * @return QueueBrokerInterface
     */
    public function getBroker()
    {
        $this->broker->setServiceLocator($this->getServiceLocator());

        return $this->broker;
    }

    /**
     * @inheritdoc
     */
    public function enqueue(TaskInterface $task, $label = null)
    {
        try {
            if (!is_null($label)) {
                $task->setLabel($label);
            }

            $isEnqueued = $this->getBroker()->push($task);

            if ($isEnqueued) {
                $this->getTaskLog()
                    ->add($task, TaskLogInterface::STATUS_ENQUEUED, $label);
            }

            return $isEnqueued;
        } catch (\Exception $e) {
            $this->logError('Enqueueing '. $task .' failed with MSG: '. $e->getMessage());
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function dequeue()
    {
        if ($task = $this->getBroker()->pop()) {
            $this->getTaskLog()
                ->setStatus($task->getId(), TaskLogInterface::STATUS_DEQUEUED);

            return $task;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function acknowledge(TaskInterface $task)
    {
        $this->getBroker()->delete($task);
    }

    /**
     * Count of messages in the queue.
     *
     * @return int
     */
    public function count()
    {
        return $this->getBroker()->count();
    }

    /**
     * @return bool
     */
    public function isSync()
    {
        return $this->broker instanceof SyncQueueBrokerInterface;
    }

    /**
     * @inheritdoc
     */
    public function getNumberOfTasksToReceive()
    {
        return $this->getBroker()->getNumberOfTasksToReceive();
    }
}