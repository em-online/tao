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

namespace oat\taoTaskQueue\test\model\ValueObjects;


use oat\taoTaskQueue\model\ValueObjects\TaskLogCategorizedStatus;

/**
 * @deprecated
 */
class TaskLogCategorizedStatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @throws \Exception
     */
    public function testCreateWithValidStatus()
    {
        $status = TaskLogCategorizedStatus::createFromString('enqueued');
        $this->assertInstanceOf(TaskLogCategorizedStatus::class, $status);
    }

    /**
     * @expectedException \Exception
     * @throws \Exception
     */
    public function testCreateWithInvalidStatus()
    {
        TaskLogCategorizedStatus::createFromString('some invalid status');
    }

    /**
     * @throws \Exception
     */
    public function testStatusAreMappedCorrectly()
    {
        $status = TaskLogCategorizedStatus::createFromString('enqueued');
        $this->assertSame('created', (string)$status);

        $status = TaskLogCategorizedStatus::createFromString('dequeued');
        $this->assertSame('in_progress', (string)$status);

        $status = TaskLogCategorizedStatus::createFromString('running');
        $this->assertSame('in_progress', (string)$status);

        $status = TaskLogCategorizedStatus::createFromString('completed');
        $this->assertSame('completed', (string)$status);

        $status = TaskLogCategorizedStatus::createFromString('failed');
        $this->assertSame('failed', (string)$status);

        $status = TaskLogCategorizedStatus::createFromString('unknown');
        $this->assertSame('failed', (string)$status);
    }

    /**
     * @throws \Exception
     */
    public function testStatusEquals()
    {
        $statusRunning = TaskLogCategorizedStatus::createFromString('dequeued');
        $this->assertTrue($statusRunning->equals(TaskLogCategorizedStatus::createFromString('dequeued')));

        $statusCompleted = TaskLogCategorizedStatus::createFromString('completed');
        $this->assertTrue($statusCompleted->equals(TaskLogCategorizedStatus::createFromString('completed')));

        $statusFailed = TaskLogCategorizedStatus::createFromString('failed');
        $this->assertTrue($statusFailed->equals(TaskLogCategorizedStatus::createFromString('unknown')));

        $this->assertFalse($statusRunning->equals($statusCompleted));
        $this->assertFalse($statusCompleted->equals($statusFailed));
    }
}
