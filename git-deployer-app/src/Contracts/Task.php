<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Contracts;

use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Model\TaskResult;

interface Task
{
    public function run(DeployerConfig $config): TaskResult;
    public function name(): string;
}
