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
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoTaskQueue\model\Entity\Decorator\CategoryEntityDecorator;
use oat\taoTaskQueue\model\Entity\Decorator\HasFileEntityDecorator;
use oat\taoTaskQueue\model\TaskLog\Decorator\SimpleManagementCollectionDecorator;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Task Log Utility.
 *
 * ```
 * $ sudo -u www-data php index.php 'oat\taoTaskQueue\scripts\tools\TaskLogUtility'
 * ```
 */

class TaskLogUtility implements Action, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    private $argStats;
    private $argAvailable;
    private $argGetTask;
    private $argHelp;
    private $argArchive;
    private $argCancel;
    private $argForce = false;
    private $argLimit;
    private $argOffset;
    private $examples = [
        [
            'title' => 'Stats',
            'description' => 'Return stats about the tasks logs statuses',
            'example' => 'sudo -u www-data php index.php \'oat\taoTaskQueue\scripts\tools\TaskLogUtility\' --stats'
        ],
        [
            'title' => 'List Task Logs',
            'description' => 'List All the tasks that are not archived will be retrived, default limit is 20',
            'example' => 'sudo -u www-data php index.php \'oat\taoTaskQueue\scripts\tools\TaskLogUtility\' --available --limit[optional]=20 --offset[optional]=10',

        ],
        [
            'title' => 'Get Task Log',
            'description' => 'Get an specific task log by id',
            'example' => 'sudo -u www-data php index.php \'oat\taoTaskQueue\scripts\tools\TaskLogUtility\' --get-task=[taskdId]'
        ],
        [
            'title' => 'Archive a Task Log',
            'description' => 'Archive a task log',
            'example' => 'sudo -u www-data php index.php \'oat\taoTaskQueue\scripts\tools\TaskLogUtility\' --archive=[taskdId] --force[optional]'
        ],
        [
            'title' => 'Cancel a Task Log',
            'description' => 'Cancel a task log',
            'example' => 'sudo -u www-data php index.php \'oat\taoTaskQueue\scripts\tools\TaskLogUtility\' --cancel=[taskdId] --force[optional]'
        ]
    ];

    public function __invoke($params)
    {
        try {
            $this->assertValidParams($params);

            /** @var TaskLogInterface $taskLog */
            $taskLog = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);

            /** @var FileSystemService $fs */
            $fs = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);

            if ($this->argStats) {
                $stats = $taskLog->getStats(TaskLogInterface::SUPER_USER);
                return \common_report_Report::createSuccess($this->jsonPretty($stats->jsonSerialize()));
            }

            if ($this->argAvailable) {
                $tasks = $taskLog->findAvailableByUser(TaskLogInterface::SUPER_USER, $this->argLimit, $this->argOffset);

                return \common_report_Report::createSuccess($this->jsonPretty(
                    (new SimpleManagementCollectionDecorator(
                        $tasks,
                        $taskLog,
                        $fs,
                        true
                    ))
                        ->jsonSerialize()
                ));
            }

            if ($this->argGetTask) {
                $task = $taskLog->getByIdAndUser($this->argGetTask, TaskLogInterface::SUPER_USER);

                return \common_report_Report::createSuccess($this->jsonPretty(
                    (new HasFileEntityDecorator(new CategoryEntityDecorator($task, $taskLog), $fs))->jsonSerialize()
                ));
            }

            if ($this->argArchive) {
                $task = $taskLog->getByIdAndUser($this->argArchive, TaskLogInterface::SUPER_USER);
                return \common_report_Report::createSuccess('Archived: ' .  $taskLog->archive($task, $this->argForce));
            }

            if ($this->argCancel) {
                $task = $taskLog->getByIdAndUser($this->argCancel, TaskLogInterface::SUPER_USER);
                return \common_report_Report::createSuccess('Cancelled: ' .  $taskLog->cancel($task, $this->argForce));
            }

            if ($this->argHelp) {
                return \common_report_Report::createSuccess($this->commandOutput($this->examples));
            }

            return \common_report_Report::createSuccess($this->commandOutput($this->examples));
        } catch (\Exception $exception) {

            $message = $exception->getMessage();

            if (!$this->argForce) {
                if ($this->argArchive) {
                    $message .= "\nPlease use --force to force archive of an in-progress task.";
                }

                if ($this->argCancel) {
                    $message .= "\nPlease use --force to force cancellation of a created task.";
                }
            }

            return \common_report_Report::createFailure($message);
        }
    }

    /**
     * @param array $params
     * @throws \Exception
     */
    private function assertValidParams(array $params)
    {
        foreach ($params as $param) {
            $args = explode('=', $param);
            $option = $args[0];
            $value = isset($args[1]) ? $args[1] : null;

            switch ($option) {
                case '--stats':
                    $this->argStats = true;
                    break;

                case '--available':
                    $this->argAvailable = true;
                    break;

                case '--limit':
                    if (!isset($this->argAvailable)) {
                        throw new \Exception('Arg --available argument must be use');
                    }
                    $this->argLimit = (int)$value;
                    break;

                case '--offset':
                    if (!isset($this->argAvailable)) {
                        throw new \Exception('Arg --available argument must be use');
                    }
                    $this->argOffset = (int)$value;
                    break;

                case '--get-task':
                    $this->argGetTask = $value;
                    if (!isset($this->argGetTask)) {
                        throw new \Exception('--get-task=[taskId] argument must be set');
                    }
                    break;

                case '--archive':
                    $this->argArchive = $value;
                    if (!isset($this->argArchive)) {
                        throw new \Exception('--archive=[taskId] argument must be set');
                    }
                    break;

                case '--cancel':
                    $this->argCancel = $value;
                    if (!isset($this->argCancel)) {
                        throw new \Exception('--cancel=[taskId] argument must be set');
                    }
                    break;

                case '--force':
                    if (!isset($this->argArchive) && !isset($this->argCancel)) {
                        throw new \Exception('--archive=[taskId] or --cancel=[taskId] argument must be set');
                    }
                    $this->argForce = true;

                    break;

                case '--help':
                    $this->argHelp = true;
                    break;

            }
        }
    }

    private function jsonPretty(array $data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function commandOutput(array $data)
    {
        $string = 'Examples';
        foreach ($data as $key => $example)
        {
            $string .= sprintf("\n %s. %s", ++$key, $example['title']);
            $string .= sprintf("\n\t Description: \t %s", $example['description']);
            $string .= sprintf("\n\t Example: \t %s", $example['example']);
        }

        return $string;
    }
}
