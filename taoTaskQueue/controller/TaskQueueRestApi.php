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

namespace oat\taoTaskQueue\controller;

use oat\tao\model\taskQueue\TaskLog\Entity\EntityInterface;
use oat\tao\model\taskQueue\TaskLogInterface;


/**
 * RestAPI controller to get data from task queue
 *
 * @deprecated Use \tao_actions_TaskQueue
 *
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class TaskQueueRestApi extends \tao_actions_RestController
{
    const PARAMETER_TASK_ID = 'id';

    /**
     * Returns the details of a task, independently from the owner.
     */
    public function get()
    {
        try {
            $this->returnSuccess($this->getTaskEntity()->toArray());
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Returns only the status of a task, independently from the owner.
     */
    public function getStatus()
    {
        try {
            $this->returnSuccess((string) $this->getTaskEntity()->getStatus());
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * @return EntityInterface
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     */
    private function getTaskEntity()
    {
        if (!$this->hasRequestParameter(self::PARAMETER_TASK_ID)) {
            throw new \common_exception_MissingParameter(self::PARAMETER_TASK_ID, $this->getRequestURI());
        }

        /** @var TaskLogInterface $taskLogService */
        $taskLogService = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);

        return $taskLogService->getById((string) $this->getRequestParameter(self::PARAMETER_TASK_ID));
    }
}