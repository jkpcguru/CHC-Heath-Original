<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Traits;

use Monolog\Handler\TestHandler;
use Monolog\Logger;

trait LoggerTrait
{
    /** @var string[] $newMessages */
    protected array $newMessages = [];

    /** @return array{messages:string[]} */
    protected function getLogs(): array
    {
        $testHandler = $this->getTestHandler();
        if ($testHandler === null) {
            return ['messages' => []];
        }

        /** @var string[] $records */
        $records = collect($testHandler->getRecords()) /* @phpstan-ignore-line */
            ->map(fn (array $record) => $record['message']) /* @phpstan-ignore-line */
            ->toArray();

        return ['messages' => $records];
    }

    protected function resetLogs(): void
    {
        $testHandler = $this->getTestHandler();
        if ($testHandler !== null) {
            $testHandler->clear();
        }

        $this->newMessages = [];
    }

    protected function getTestHandler(): ?TestHandler
    {
        $logger = $this->logger;
        if (!$logger instanceof Logger) {
            return null;
        }

        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof TestHandler) {
                return $handler;
            }
        }

        return null;
    }
}
