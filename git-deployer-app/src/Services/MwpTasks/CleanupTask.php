<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Services\MwpTasks;

use Pagely\GitDeployer\Helpers\MwpPathHelper;
use Pagely\GitDeployer\Helpers\ShellCommandHelper;
use Pagely\GitDeployer\Logging\PhpStdOutLogger;
use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Task\AbstractTask;
use Psr\Log\LoggerInterface;

final class CleanupTask extends AbstractTask
{
    public function __construct(
        LoggerInterface         $logger,
        private MwpPathHelper   $pathHelper,
        private PhpStdOutLogger $printLogger,
    ) {
        parent::__construct($logger, $printLogger);
    }

    protected function exec(DeployerConfig $config): void
    {
        $this->printLogger->info("Clearing resources...");

        $tempDeployDir = $this->pathHelper->getDeployTempDirPath($config);
        ShellCommandHelper::deleteFile($tempDeployDir);
        $this->logger->info("Removed temp dir: ".$tempDeployDir);

        $tarFile = $this->pathHelper->getTarFilePath($config);
        ShellCommandHelper::deleteFile($tarFile);
        $this->logger->info("Removed tar file: ".$tarFile);

        $this->printLogger->info("Cleanup done.");
    }

    public function name(): string
    {
        return "Cleanup";
    }

    protected function shouldSkip(DeployerConfig $config): bool
    {
        return false;
    }
}
