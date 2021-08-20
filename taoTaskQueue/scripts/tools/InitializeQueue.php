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

use common_report_Report as Report;
use oat\oatbox\extension\InstallAction;
use Aws\Exception\AwsException;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\Queue\Broker\InMemoryQueueBroker;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoTaskQueue\model\QueueBroker\RdsQueueBroker;
use oat\taoTaskQueue\model\QueueBroker\SqsQueueBroker;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * - Without any parameter, it uses the current settings for initialization:
 *   - creates the queues
 *   - creates the task log container
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\InitializeQueue'
 * ```
 *
 * - Using Sync Queues. Every existing queue will be changed to use InMemoryQueueBroker if there is no queue specified.
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\InitializeQueue' --broker=memory [--queue=myQueue]
 * ```
 *
 * - Using RDS Queues. Every existing queue will be changed to use RdsQueueBroker if there is no queue specified. You can set the following parameters:
 *  - persistence: Required
 *  - receive: Optional (Maximum amount of tasks that can be received when polling the queue)
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\InitializeQueue' --broker=rds --persistence=default --receive=10 [--queue=myQueue]
 * ```
 *
 * - Using SQS Queues. Every existing queue will be changed to use SqsQueueBroker if there is no queue specified. You can set the following parameters:
 *  - receive: Optional (Maximum amount of tasks that can be received when polling the queue)
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\InitializeQueue' --broker=sqs --receive=10 [--queue=myQueue]
 * ```
 *
 * - To set a task selector strategy, please provide the FQCN of the wanted strategy
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\InitializeQueue' --strategy="\oat\taoTaskQueue\model\TaskSelector\StrictPriorityStrategy"
 */
class InitializeQueue extends InstallAction
{
    use ServiceLocatorAwareTrait;

    const BROKER_MEMORY = 'memory';
    const BROKER_RDS = 'rds';
    const BROKER_SQS = 'sqs';

    private $wantedBroker;
    private $persistenceId;
    private $receive;
    private $queue;
    private $strategy;

    public function __invoke($params)
    {
        try {
            $this->checkParams($params);

            $report = Report::createInfo('Running command...');

            /** @var QueueDispatcherInterface|ConfigurableService $queueService */
            $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);

            $reRegister = false;

            // if any new change is wanted on queues
            if (count($params) > 0) {

                // BROKER settings
                if ($this->wantedBroker) {
                    $broker = null;

                    switch ($this->wantedBroker) {
                        case self::BROKER_MEMORY:
                            $broker = new InMemoryQueueBroker();
                            break;

                        case self::BROKER_RDS:
                            $broker = new RdsQueueBroker($this->persistenceId, $this->receive ?: 1);
                            break;

                        case self::BROKER_SQS:
                            $broker = new SqsQueueBroker(\common_cache_Cache::SERVICE_ID, $this->receive ?: 1);
                            break;
                    }

                    if (!is_null($this->queue)){
                        $queue = $queueService->getQueue($this->queue);
                        $queue->setBroker(clone $broker);
                    } else {
                        foreach ($queueService->getQueues() as $queue) {
                            $queue->setBroker(clone $broker);
                        }
                    }
                }

                // STRATEGY settings
                if ($this->strategy) {
                    $queueService->setTaskSelector($this->strategy);
                }

                $reRegister = true;
            }

            // Create queues
            if (!$queueService->isSync()) {
                $queueService->initialize();
                $report->add(Report::createSuccess('Queue(s) initialized.'));
            }

            if ($reRegister) {
                $this->registerService(QueueDispatcherInterface::SERVICE_ID, $queueService);
                $report->add(Report::createSuccess('Queue service re-registered.'));
            }

            // Create task log container
            /** @var TaskLogInterface $taskLog */
            $taskLog = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);
            $taskLog->createContainer();
            $report->add(Report::createSuccess('Task Log container created.'));

            return $report;
        } catch (\Exception $e) {
            return Report::createFailure($e->getMessage());
        }
    }

    /**
     * @param array $params
     */
    private function checkParams(array $params)
    {
        foreach ($params as $param) {
            list($option, $value) = explode('=', $param);

            switch ($option) {
                case '--broker':
                    if (!in_array($value, [self::BROKER_MEMORY, self::BROKER_RDS, self::BROKER_SQS])) {
                        throw new \InvalidArgumentException('Broker "'. $value .'" is not a valid broker option. Valid options: '. implode(', ', [self::BROKER_MEMORY, self::BROKER_RDS, self::BROKER_SQS]));
                    }

                    $this->wantedBroker = $value;
                    break;

                case '--persistence':
                    $this->persistenceId = $value;
                    break;

                case '--receive':
                    $this->receive = abs((int) $value);
                    break;

                case '--queue':
                    $this->queue = (string) $value;
                    break;

                case '--strategy':
                    if (!class_exists($value)) {
                        throw new \InvalidArgumentException('Strategy "'. $value .'" does not exist.');
                    }

                    $this->strategy = new $value();
                    break;
            }
        }

        if ($this->wantedBroker == self::BROKER_RDS && !$this->persistenceId) {
            throw new \InvalidArgumentException('Persistence id (--persistence=...) needs to be set for RDS.');
        }
    }
}

