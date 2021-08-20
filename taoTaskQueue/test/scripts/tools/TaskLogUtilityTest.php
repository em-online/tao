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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoTaskQueue\test\scripts\tools;

use common_report_Report;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\taskQueue\TaskLog\CategorizedStatus;
use oat\tao\model\taskQueue\TaskLog\Entity\TaskLogEntity;
use oat\tao\model\taskQueue\TaskLog\TaskLogCollection;
use oat\tao\model\taskQueue\TaskLog\TasksLogsStats;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoTaskQueue\scripts\tools\TaskLogUtility;

class TaskLogUtilityTest extends TestCase
{
    /** @var TaskLogUtility */
    private $subject;

    /** @var TaskLogInterface */
    private $taskLogMock;

    protected function setUp()
    {
        parent::setUp();

        $this->taskLogMock = $this->createMock(TaskLogInterface::class);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            TaskLogInterface::SERVICE_ID => $this->taskLogMock,
            FileSystemService::SERVICE_ID => $this->createMock(FileSystemService::class),
        ]);

        $this->subject = new TaskLogUtility();
        $this->subject->setServiceLocator($serviceLocatorMock);
    }

    public function testHelp()
    {
        $output = $this->subject->__invoke(['--help']);

        $this->assertInstanceOf(common_report_Report::class, $output);
        $this->assertEquals("Examples
 1. Stats
	 Description: 	 Return stats about the tasks logs statuses
	 Example: 	 sudo -u www-data php index.php 'oat\\taoTaskQueue\scripts\\tools\TaskLogUtility' --stats
 2. List Task Logs
	 Description: 	 List All the tasks that are not archived will be retrived, default limit is 20
	 Example: 	 sudo -u www-data php index.php 'oat\\taoTaskQueue\scripts\\tools\TaskLogUtility' --available --limit[optional]=20 --offset[optional]=10
 3. Get Task Log
	 Description: 	 Get an specific task log by id
	 Example: 	 sudo -u www-data php index.php 'oat\\taoTaskQueue\scripts\\tools\TaskLogUtility' --get-task=[taskdId]
 4. Archive a Task Log
	 Description: 	 Archive a task log
	 Example: 	 sudo -u www-data php index.php 'oat\\taoTaskQueue\scripts\\tools\TaskLogUtility' --archive=[taskdId] --force[optional]
 5. Cancel a Task Log
	 Description: 	 Cancel a task log
	 Example: 	 sudo -u www-data php index.php 'oat\\taoTaskQueue\scripts\\tools\TaskLogUtility' --cancel=[taskdId] --force[optional]",
            $output->getMessage()
        );
    }

    public function testGetStats()
    {
        $stats = new TasksLogsStats(1, 2, 3);

        $this->taskLogMock
            ->expects($this->once())
            ->method('getStats')
            ->with(TaskLogInterface::SUPER_USER)
            ->willReturn($stats);

        $output = $this->subject->__invoke(['--stats']);

        $this->assertInstanceOf(common_report_Report::class, $output);
        $this->assertContains('"numberOfTasksCompleted": 1', $output->getMessage());
        $this->assertContains('"numberOfTasksFailed": 2', $output->getMessage());
        $this->assertContains('"numberOfTasksInProgress": 3', $output->getMessage());
    }

    public function testFindAvailableByUser()
    {
        $collection = TaskLogCollection::createEmptyCollection();

        $this->taskLogMock
            ->expects($this->once())
            ->method('findAvailableByUser')
            ->with(TaskLogInterface::SUPER_USER, 10, 0)
            ->willReturn($collection);

        $output = $this->subject->__invoke(['--available', '--limit=10', '--offset=0']);

        $this->assertInstanceOf(common_report_Report::class, $output);
        $this->assertEquals('[]', $output->getMessage());
    }

    public function testGetTask()
    {
        $taskLog = $this->createTakLogEntity('id', 'parentId', 'name', CategorizedStatus::created());

        $this->taskLogMock
            ->expects($this->once())
            ->method('getByIdAndUser')
            ->with('id', TaskLogInterface::SUPER_USER)
            ->willReturn($taskLog);

        $output = $this->subject->__invoke(['--get-task=id']);

        $this->assertInstanceOf(common_report_Report::class, $output);
        $this->assertContains('"id": "id"', $output->getMessage());
        $this->assertContains('"taskName": "name"', $output->getMessage());
        $this->assertContains('"status": "' . CategorizedStatus::created() . '"', $output->getMessage());
    }

    public function testArchive()
    {
        $taskLog = $this->createTakLogEntity('id', 'parentId', 'name', CategorizedStatus::created());

        $this->taskLogMock
            ->expects($this->once())
            ->method('getByIdAndUser')
            ->with('id', TaskLogInterface::SUPER_USER)
            ->willReturn($taskLog);

        $this->taskLogMock
            ->expects($this->once())
            ->method('archive')
            ->with($taskLog, true)
            ->willReturn(true);

        $output = $this->subject->__invoke(['--archive=id', '--force']);

        $this->assertInstanceOf(common_report_Report::class, $output);
        $this->assertEquals('Archived: 1', $output->getMessage());
    }

    public function testCancel()
    {
        $taskLog = $this->createTakLogEntity('id', 'parentId', 'name', CategorizedStatus::created());

        $this->taskLogMock
            ->expects($this->once())
            ->method('getByIdAndUser')
            ->with('id', TaskLogInterface::SUPER_USER)
            ->willReturn($taskLog);

        $this->taskLogMock
            ->expects($this->once())
            ->method('cancel')
            ->with($taskLog, true)
            ->willReturn(true);

        $output = $this->subject->__invoke(['--cancel=id', '--force']);

        $this->assertInstanceOf(common_report_Report::class, $output);
        $this->assertEquals('Cancelled: 1', $output->getMessage());
    }

    /**
     * @param string $id
     * @param string $parentId
     * @param string $name
     * @param CategorizedStatus $status
     * @return TaskLogEntity
     */
    private function createTakLogEntity($id, $parentId, $name, $status)
    {
        return new TaskLogEntity(
            $id,
            $parentId,
            $name,
            $status,
            [],
            'label',
            'owner'
        );
    }
}
