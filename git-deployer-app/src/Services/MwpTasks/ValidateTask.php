<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Services\MwpTasks;

use Pagely\GitDeployer\Exceptions\ValidateTaskException;
use Pagely\GitDeployer\Helpers\MwpPathHelper;
use Pagely\GitDeployer\Helpers\ShellCommandHelper;
use Pagely\GitDeployer\Logging\PhpStdOutLogger;
use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Task\AbstractTask;
use Psr\Log\LoggerInterface;

final class ValidateTask extends AbstractTask
{
    public function __construct(
        LoggerInterface         $logger,
        private MwpPathHelper   $pathHelper,
        private PhpStdOutLogger $printLogger,
    ) {
        parent::__construct($logger, $printLogger);
    }

    /**
     * @throws ValidateTaskException
     */
    protected function exec(DeployerConfig $config): void
    {
        $this->printLogger->info("Validating request...");
        if (!\file_exists($this->pathHelper->getTarFilePath($config))) {
            $this->logger->error("{$config->tarFile} Tar file does not exist.");
            $this->printLogger->error("{$config->tarFile} Tar file does not exist");
            throw ValidateTaskException::tarDoesNotExist($config->tarFile);
        }

        if (!\is_dir($config->deployDir) || !\file_exists($config->deployDir)) {
            $this->logger->error("{$config->deployDir} Deployment directory does not exist");
            $this->printLogger->error("{$config->deployDir} Deployment directory does not exist");
            throw ValidateTaskException::deployDestDoesNotExist($config->deployDir);
        }

        // if it's not healthy here, no need to check health again for rollback
        $config->setWPHealthPreDeploy(
            ShellCommandHelper::isWordPressHealthy($this->pathHelper->getWordpressRootDir())
        );

        $this->printLogger->info("Request Validated.");
    }

    public function name(): string
    {
        return "Validate";
    }

    protected function shouldSkip(DeployerConfig $config): bool
    {
        return false;
    }
}
