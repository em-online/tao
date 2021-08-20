<?php

namespace oat\taoTaskQueue\model\Task;

/**
 * @deprecated Use \oat\tao\model\taskQueue\Task\RemoteTaskSynchroniserInterface
 */
interface RemoteTaskSynchroniserInterface
{
    public function getRemoteStatus();
}