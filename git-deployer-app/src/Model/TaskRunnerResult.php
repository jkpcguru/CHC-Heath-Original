<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Model;

class TaskRunnerResult
{
    use ModelTrait;

    /**
     * @var TaskResult[] $taskResults
     */
    private array $taskResults;

    public function addTaskResult(TaskResult $taskResult): void
    {
        $this->taskResults[] = $taskResult;
    }
}
