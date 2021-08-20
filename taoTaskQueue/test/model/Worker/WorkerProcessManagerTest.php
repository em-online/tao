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
namespace oat\taoTaskQueue\test\model\Worker;

use oat\taoTaskQueue\model\Worker\WorkerProcessManager;
use React\ChildProcess\Process;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

class WorkerProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testAddProcess()
    {
        $workerManager = new WorkerProcessManager();

        $process1 = $this->getMockBuilder(Process::class)->disableOriginalConstructor()->getMock();
        $process1
            ->method('getPid')->willReturn(1);
        $process1->stdout =$this->getMockBuilder(ReadableStreamInterface::class)->disableOriginalConstructor()->getMock();
        $process1->stdin =$this->getMockBuilder(WritableStreamInterface::class)->disableOriginalConstructor()->getMock();

        $process2 = $this->getMockBuilder(Process::class)->disableOriginalConstructor()->getMock();
        $process2->stdout =$this->getMockBuilder(ReadableStreamInterface::class)->disableOriginalConstructor()->getMock();
        $process2->stdin =$this->getMockBuilder(WritableStreamInterface::class)->disableOriginalConstructor()->getMock();
        $process1
            ->method('getPid')->willReturn(2);
        $workerManager->addProcess($process1);
        $workerManager->addProcess($process2);

        $this->assertCount(2, $workerManager->getProcesses());
    }

    public function testCanRunWithSuccess()
    {
        /** @var WorkerProcessManager $workerManager */
        $workerManager = $this->getMockBuilder(WorkerProcessManager::class)
            ->setMethods(['logInfo','getMemoryUsage','getCpuUsage'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $workerManager
            ->method('getMemoryUsage')->willReturn(50);
        $workerManager
            ->method('getCpuUsage')->willReturn(50);

        $workerManager->setLimitOfMemory(80);
        $workerManager->setLimitOfCpu(80);
        $this->assertTrue($workerManager->canRun());
    }

    public function testCanRunFailed()
    {
        /** @var WorkerProcessManager $workerManager */
        $workerManager = $this->getMockBuilder(WorkerProcessManager::class)
            ->setMethods(['logInfo','getMemoryUsage','getCpuUsage'])
            ->disableOriginalConstructor()->getMockForAbstractClass();

        $workerManager
            ->method('getMemoryUsage')->willReturn(90);
        $workerManager
            ->method('getCpuUsage')->willReturn(90);

        $workerManager->setLimitOfMemory(80);
        $workerManager->setLimitOfCpu(80);

        $this->assertFalse($workerManager->canRun());
    }

    public function testGetCommand()
    {
        $workerManager = new WorkerProcessManager();
        $workerManager->setOption(WorkerProcessManager::OPTION_TASK_COMMAND, 'some task');
        $this->assertSame('some task', $workerManager->getCommand());
    }

    public function testGetMemoryUsage()
    {
        $workerManager = new WorkerProcessManager();

        $this->assertInternalType('numeric', $workerManager->getMemoryUsage());
    }

    public function testGetCpuUsage()
    {
        $workerManager = new WorkerProcessManager();

        $this->assertInternalType('float', $workerManager->getCpuUsage());
    }
}
