<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Services;

use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Services\MwpTasks\CleanupTask;
use Pagely\GitDeployer\Services\MwpTasks\CreateManifestFileTask;
use Pagely\GitDeployer\Services\MwpTasks\ExtractAndSyncTask;
use Pagely\GitDeployer\Services\MwpTasks\FlushCacheTask;
use Pagely\GitDeployer\Services\MwpTasks\ValidateTask;
use Pagely\GitDeployer\Task\TaskRunner;

class MwpDeployer
{
    public const TASKS = [
        ValidateTask::class,
        CreateManifestFileTask::class,
        ExtractAndSyncTask::class,
        FlushCacheTask::class,
        CleanupTask::class,
    ];

    public const ON_FAIL_TASKS = [
        CleanupTask::class,
    ];

    public function __construct(private TaskRunner $taskRunner)
    {
    }

    public function run(
        string $tarFile,
        string $deployDir,
        bool $healthCheck,
        ?string $postDeployCmd,
    ): void {
        $config = $this->getConfig(
            $tarFile,
            $deployDir,
            $postDeployCmd,
            $healthCheck
        );
        $this->taskRunner->runTasks(self::TASKS, $config, self::ON_FAIL_TASKS);
    }

    public function getConfig(
        string $tarFile,
        string $deployDir,
        ?string $postDeployCmd,
        bool $healthCheck = true,
    ): DeployerConfig {
        $config = [
            'tarFile' => $tarFile,
            'deployDir' => \rtrim($deployDir, '/'),
            'postDeployCmd' => $postDeployCmd,
            'healthCheck' => $healthCheck,
        ];
        return new DeployerConfig($config);
    }
}
