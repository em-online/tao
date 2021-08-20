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

namespace oat\taoTaskQueue\model\TaskSelector;

use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\PhpSerializeStateless;
use oat\tao\model\taskQueue\Queue\TaskSelector\SelectorStrategyInterface;
use oat\tao\model\taskQueue\QueueInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Walking through all the queues having respect for the priorities.
 *
 * 1: generating a list of queues in the order of weights
 * 2: get a queue by the current index (starting with the highest priority queue always)
 * 3a: IF there is a task in the current queue, let's process it and restart form point 2
 * 3b: IF there is no task go to the next queue and repeat from point 2
 * 3c: IF there is no task and we have a complete iteration, sleep a while and repeat from point 2
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class StrictPriorityStrategy implements SelectorStrategyInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PhpSerializeStateless;

    /**
     * @var QueueInterface[]
     */
    private $sortedQueues = [];

    private $nextQueueIndex = 0;

    /**
     * @inheritdoc
     */
    public function pickNextTask(array $queues)
    {
        $this->sortQueues($queues);

        // if the next index is bigger than the max index
        if ($this->nextQueueIndex > count($this->sortedQueues) - 1) {
            $this->nextQueueIndex = 0;
        }

        $pickedQueue = $this->sortedQueues[$this->nextQueueIndex];

        $this->logDebug('Queue "' . strtoupper($pickedQueue->getName()) . '" picked by StrictPriorityStrategy');

        $task = $pickedQueue->dequeue();

        if (is_null($task)) {
            // let's use the next queue in the next iteration
            $this->nextQueueIndex++;
        } else {
            // always start with the first queue after having a task from any queue
            $this->nextQueueIndex = 0;
        }

        return $task;
    }

    /**
     * @return int
     */
    public function getWaitTime()
    {
        // sleeping 5 sec only after a complete iteration (every queue has been selected once in a row), otherwise 0 sec
        return $this->nextQueueIndex - 1 == count($this->sortedQueues) - 1 ? 5 : 0;
    }

    /**
     * @param QueueInterface[] $queues
     */
    private function sortQueues(array $queues)
    {
        usort($queues, function (QueueInterface $queueA, QueueInterface $queueB) {
            if ($queueA->getWeight() == $queueB->getWeight()) {
                return 0;
            }

            return ($queueB->getWeight() < $queueA->getWeight()) ? -1 : 1;
        });

        $this->sortedQueues = $queues;
    }
}
