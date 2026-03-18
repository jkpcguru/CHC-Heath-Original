<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Services\MwpTasks;

use Pagely\GitDeployer\Helpers\MwpPathHelper;
use Pagely\GitDeployer\Helpers\ShellCommandHelper;
use Pagely\GitDeployer\Logging\PhpStdOutLogger;
use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Task\AbstractTask;
use Psr\Log\LoggerInterface;

final class FlushCacheTask extends AbstractTask
{
    public function __construct(
        LoggerInterface         $logger,
        private MwpPathHelper   $pathHelper,
        private PhpStdOutLogger $printLogger,
    ) {
        parent::__construct($logger, $this->printLogger);
    }

    protected function exec(DeployerConfig $config): void
    {
        $this->printLogger->info("Flushing cache...");

        $this->logger->info("Flushing cache");
        $wpRoot = $this->pathHelper->getWordpressRootDir();

        $output = ShellCommandHelper::wpCacheFlush($wpRoot);

        if ($output['exitCode'] !== 0) {
            $message = "Flush Cache failed with code {$output['exitCode']}";
            $loggerMessage = $message . ' | Output: ' . $output['output']. ' | ExitCode: '. $output['exitCode'];
            // not throwing error as flush-cache is not blocker for process further
            $this->logger->error($loggerMessage);
            $this->printLogger->error($message);
        } else {
            $this->logger->info("Cache flushed: ". \implode("  ", $output));
            $this->printLogger->info("Cache flushed");
        }
    }

    public function name(): string
    {
        return "Flush Cache";
    }

    protected function shouldSkip(DeployerConfig $config): bool
    {
        return false;
    }
}
