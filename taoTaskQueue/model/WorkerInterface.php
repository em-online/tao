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

use oat\taoTaskQueue\model\Task\TaskInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Interface WorkerInterface
 *
 * @deprecated Use \oat\tao\model\taskQueue\Worker\WorkerInterface
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
interface WorkerInterface extends LoggerAwareInterface
{
    /**
     * @param QueueDispatcherInterface $queueService
     * @param TaskLogInterface         $taskLog
     * @param bool                     $handleSignals
     */
    public function __construct(QueueDispatcherInterface $queueService, TaskLogInterface $taskLog, $handleSignals);

    /**
     * Start processing tasks from a given queue
     *
     * @return void
     */
    public function run();

    /**
     * Process a task
     *
     * @param  TaskInterface $task
     * @return string Status of the task after process
     */
    public function processTask(TaskInterface $task);

    /**
     * Set the maximum iterations for the worker. If nothing is set, the worker runs infinitely.
     * @deprecated
     *
     * @param int $maxIterations
     * @return WorkerInterface
     */
    public function setMaxIterations($maxIterations);

    /**
     * Sets a queue on which the worker operates exclusively.
     *
     * @param QueueInterface $queue
     * @param int $maxIterations
     *
     * @return WorkerInterface
     */
    public function setDedicatedQueue(QueueInterface $queue, $maxIterations = 0);
}