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

namespace oat\taoTaskQueue\test\model;

use oat\oatbox\service\ServiceManager;
use oat\taoTaskQueue\model\Entity\TaskLogEntity;
use oat\taoTaskQueue\model\TaskLog;
use oat\taoTaskQueue\model\TaskLogActionTrait;
use oat\taoTaskQueue\model\ValueObjects\TaskLogCategorizedStatus;

class TaskLogActionTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTaskLogEntityWhenOnlyTaskIdIsProvidedThenGetTheDefaultUser()
    {
        $taskLogEntityMock = $this->getMockBuilder(TaskLogEntity::class)
            ->disableOriginalConstructor()
            ->getMock();

        $taskLogMock = $this->getMockBuilder(TaskLog::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByIdAndUser'])
            ->getMock();

        $taskLogMock->expects($this->once())
            ->method('getByIdAndUser')
            ->willReturn($taskLogEntityMock);

        $serviceManagerMock = $this->getMockBuilder(ServiceManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $serviceManagerMock->expects($this->once())
            ->method('get')
            ->willReturn($taskLogMock);

        $traitMock = $this->getMockBuilder(TaskLogActionTrait::class)
            ->setMethods(['getServiceManager', 'getUserId'])
            ->getMockForTrait();

        $traitMock->expects($this->once())
            ->method('getServiceManager')
            ->willReturn($serviceManagerMock);

        $traitMock->expects($this->once())
            ->method('getUserId');

        $entityCaller = function () {
            return $this->getTaskLogEntity('fakeTaskId');
        };

        $bound = $entityCaller->bindTo($traitMock, $traitMock);

        $this->assertInstanceOf(TaskLogEntity::class, $bound());
    }

    public function testTaskIdGetter()
    {
        $fakeId = 'fakeTaskId#000111';

        $taskLogEntityMock = $this->getTaskLogEntityMock();

        $taskLogEntityMock->expects($this->once())
            ->method('getId')
            ->willReturn($fakeId);

        $traitMock = $this->getMockBuilder(TaskLogActionTrait::class)
            ->getMockForTrait();

        $idCaller = function () use ($taskLogEntityMock) {
            return $this->getTaskId($taskLogEntityMock);
        };
        $bound = $idCaller->bindTo($traitMock, $traitMock);
        $this->assertEquals($fakeId, $bound());
    }

    public function testTaskStatusGetter()
    {
        $fakeLabel = 'fakeStatus Label';

        $categorizedStatusMock = $this->getMockBuilder(TaskLogCategorizedStatus::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLabel'])
            ->getMock();

        $categorizedStatusMock->expects($this->once())
            ->method('getLabel')
            ->willReturn($fakeLabel);

        $taskLogEntityMock = $this->getTaskLogEntityMock();

        $taskLogEntityMock->expects($this->once())
            ->method('getStatus')
            ->willReturn($categorizedStatusMock);

        $traitMock = $this->getMockBuilder(TaskLogActionTrait::class)
            ->getMockForTrait();

        $statusCaller = function () use ($taskLogEntityMock) {
            return $this->getTaskStatus($taskLogEntityMock);
        };
        $bound = $statusCaller->bindTo($traitMock, $traitMock);
        $this->assertEquals($fakeLabel, $bound());
    }

    public function testTaskReportGetter()
    {
        $reportMock = $this->getMockBuilder(\common_report_Report::class)
            ->disableOriginalConstructor()
            ->getMock();

        $taskLogEntityMock = $this->getTaskLogEntityMock();

        $taskLogEntityMock->expects($this->once())
            ->method('getReport')
            ->willReturn($reportMock);

        $traitMock = $this->getMockBuilder(TaskLogActionTrait::class)
            ->setMethods(['getReportAsAssociativeArray'])
            ->getMockForTrait();

        $expectedReport[] = [
            'type'    => 'info',
            'message' => 'Running task http://www.taoinstance.dev/ontologies/tao.rdf#i1508337970199318643',
        ];

        $traitMock->expects($this->once())
            ->method('getReportAsAssociativeArray')
            ->willReturn($expectedReport);

        $reportCaller = function () use ($taskLogEntityMock) {
            return $this->getTaskReport($taskLogEntityMock);
        };
        $bound = $reportCaller->bindTo($traitMock, $traitMock);
        $this->assertEquals($expectedReport, $bound());
    }

    protected function getTaskLogEntityMock()
    {
        $taskLogEntityMock = $this->getMockBuilder(TaskLogEntity::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getStatus', 'getReport', 'getTaskName'])
            ->getMock();

        return $taskLogEntityMock;
    }

    /**
     * @expectedException \common_exception_BadRequest
     */
    public function testGetTaskLogReturnDataWhenTaskTypeIsProvidedButTheTaskIdBelongsToDifferentTypeOfTaskThenThrowException()
    {
        $taskLogEntityMock = $this->getTaskLogEntityMock();

        $taskLogEntityMock->expects($this->once())
            ->method('getTaskName')
            ->willReturn('oat\taoDeliveryRdf\model\tasks\ImportAndCompile');

        $traitMock = $this->getMockBuilder(TaskLogActionTrait::class)
            ->setMethods(['getTaskLogEntity'])
            ->getMockForTrait();

        $traitMock->expects($this->once())
            ->method('getTaskLogEntity')
            ->willReturn($taskLogEntityMock);

        $dataCaller = function () {
            return $this->getTaskLogReturnData('fakeId', 'oat\taoDeliveryRdf\model\tasks\CompileDelivery');
        };
        $bound = $dataCaller->bindTo($traitMock, $traitMock);

        $bound();
    }

    public function testGetTaskLogForProperResult()
    {
        $taskId = 'fakeTaskId#2536485';
        $statusLabel = 'fakeStatus Label';
        $reportArray = [
            'type' => 'fakeType',
            'message' => 'fakeMessage'
        ];

        $taskLogEntityMock = $this->getTaskLogEntityMock();

        $taskLogEntityMock->expects($this->once())
            ->method('getReport')
            ->willReturn(true);

        $traitMock = $this->getMockBuilder(TaskLogActionTrait::class)
            ->setMethods(['getTaskLogEntity', 'getTaskId', 'getTaskStatus', 'getTaskReport'])
            ->getMockForTrait();

        $traitMock->expects($this->once())
            ->method('getTaskLogEntity')
            ->willReturn($taskLogEntityMock);

        $traitMock->expects($this->once())
            ->method('getTaskId')
            ->willReturn($taskId);

        $traitMock->expects($this->once())
            ->method('getTaskStatus')
            ->willReturn($statusLabel);

        $traitMock->expects($this->once())
            ->method('getTaskReport')
            ->willReturn($reportArray);

        $dataCaller = function () use ($taskId) {
            return $this->getTaskLogReturnData($taskId);
        };
        $bound = $dataCaller->bindTo($traitMock, $traitMock);

        $expectedData = [
            'id' => $taskId,
            'status' => $statusLabel,
            'report' => $reportArray
        ];

        $this->assertEquals($expectedData, $bound());
    }
}