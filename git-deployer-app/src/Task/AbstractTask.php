<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Task;

use Pagely\GitDeployer\Contracts\Task;
use Pagely\GitDeployer\Enums\JobStatus;
use Pagely\GitDeployer\Logging\PhpStdOutLogger;
use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Model\TaskResult;
use Pagely\GitDeployer\Traits\LoggerTrait;
use Psr\Log\LoggerInterface;

abstract class AbstractTask implements Task
{
    use LoggerTrait;

    abstract protected function exec(DeployerConfig $config): void;

    public function __construct(
        protected LoggerInterface $logger,
        private PhpStdOutLogger $printLogger,
    ) {
        $this->resetLogs();
    }

    public function run(
        DeployerConfig $config,
    ): TaskResult {
        $start = new \DateTimeImmutable();

        $this->logger->info("Starting task: ".$this->name());

        if ($this->shouldSkip($config)) {
            $this->logger->info('Skipping task: ' . $this->name());
            $this->newMessages = $this->getLogs()['messages'];

            return new TaskResult([
                'name' => $this->name(),
                'start' => $start,
                'stop' => new \DateTimeImmutable(),
                'ran' => false,
                'success' => true,
            ]);
        }

        $this->logger->info('Running task: ' . $this->name());
        $this->newMessages = $this->getLogs()['messages'];

        $success = true;
        try {
            $this->exec($config);
        } catch (\Throwable $error) {
            $this->logger->error($error->getMessage(), [
                'task' => $this->name(),
                'config' => $config->toArray(),
                'error' => $error->getMessage(),
                'trace' => $error->getTraceAsString(),
            ]);
            $this->printLogger->error("Error while executing ".$this->name());
            $success = false;
        }

        // reset
        $this->newMessages = $this->getLogs()['messages'];

        if (isset($error)) {
            $this->newMessages[] = $error->getMessage();
        }

        $successStatus = $success ? JobStatus::Success : JobStatus::Failure;
        $this->logger->info("Completed task ".$this->name()." with " . $successStatus->value);

        return new TaskResult([
            'name' => $this->name(),
            'start' => $start,
            'stop' => new \DateTimeImmutable(),
            'ran' => true,
            'success' => $success,
            'message' => isset($error) ? $error->getMessage() : null,
        ]);
    }

    protected function shouldSkip(DeployerConfig $config): bool
    {
        return false;
    }
}
