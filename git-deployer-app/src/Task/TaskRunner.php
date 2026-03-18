<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Task;

use Auryn\InjectionException;
use Auryn\Injector;
use Pagely\GitDeployer\Contracts\Task;
use Pagely\GitDeployer\Exceptions\GitDeployerException;
use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Model\TaskRunnerResult;
use Psr\Log\LoggerInterface;

class TaskRunner
{
    public function __construct(
        private Injector $injector,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string> $tasks
     * @param array<string> $onFailTasks
     * @throws InjectionException
     * @throws GitDeployerException
     */
    public function runTasks(
        array $tasks,
        DeployerConfig $config,
        array $onFailTasks = [],
        bool $isOnFailRunner = false,
    ): TaskRunnerResult {
        $runnerName = 'git-deployer';
        if ($isOnFailRunner) {
            $runnerName = 'on-fail-runner';
        }

        $result = new TaskRunnerResult();
        foreach ($tasks as $taskClass) {
            /** @var Task $task */
            $task = $this->injector->make($taskClass);

            $taskResult = $task->run($config);
            $result->addTaskResult($taskResult);

            if (!$taskResult->success) {
                $this->logger->error($runnerName.' task failed', [
                    'task' => $task->name(),
                    'status' => 'failed',
                    'error' => $taskResult->message,
                ]);

                // run task when something goes wrong within tasks
                if (!empty($onFailTasks)) {
                    $this->logger->info('Starting OnFail runner execution', [
                        'tasks' => \implode(', ', $onFailTasks),
                    ]);
                    $this->runTasks($onFailTasks, $config, [], true);
                }

                throw new GitDeployerException("{$runnerName} {$taskClass} task failed");
            }
        }

        $this->logger->info($runnerName.' complete. Status: Success');

        return $result;
    }
}
