<?php


use oat\taoTaskQueue\model\Worker\WorkerProcessManager;

return new WorkerProcessManager([
    WorkerProcessManager::OPTION_TASK_COMMAND => 'php index.php "\oat\taoTaskQueue\scripts\tools\RunTask"'
]);