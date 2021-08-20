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
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoTaskQueue\model\LongRunningWorker;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Start a new worker.
 *
 * - Working on all registered queue based on weights
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\RunWorker'
 * ```
 *
 * - Working on a dedicated queue
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\RunWorker' --queue=Queue1
 * ```
 *
 * - Working on a dedicated queue until the given iteration is reached
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\RunWorker' --queue=Queue2 --limit=5
 * ```
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class RunWorker implements Action, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    public function __invoke($params)
    {
        $queue = null;
        $limit = 0;

        /** @var QueueDispatcherInterface $queueService */
        $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);

        if ($queueService->isSync()) {
            return \common_report_Report::createInfo('No worker is needed because all registered queue is a Sync Queue.');
        }

        foreach ($params as $param) {
            list($option, $value) = explode('=', $param);

            switch ($option) {
                case '--queue':
                    $queue = $queueService->getQueue($value);
                    break;

                case '--limit':
                    $limit = (int) $value;
                    break;
            }
        }

        /** @var TaskLogInterface $taskLog */
        $taskLog = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);

        // if it is install on windows do not use the signals pcntl (specific for ubuntu).
        $handleSignals = stripos(PHP_OS, 'win') === 0 ? false : true;

        try {
            $worker = new LongRunningWorker($queue ?: $queueService, $taskLog, $handleSignals);

            if ($limit) {
                $worker->setMaxIterations($limit);
            }

            $worker->run();
        } catch (\Exception $e) {
            return \common_report_Report::createFailure($e->getMessage());
        }

        return \common_report_Report::createSuccess('Worker finished');
    }
}

