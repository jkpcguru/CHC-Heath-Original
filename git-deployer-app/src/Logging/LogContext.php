<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Logging;

final class LogContext
{
    private static ?string $executionId = null;

    public static function getExecutionId(): string
    {
        if (!self::$executionId) {
            self::$executionId = \bin2hex(\random_bytes(8));
        }

        return self::$executionId;
    }
}
