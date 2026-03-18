<?php declare(strict_types=1);

namespace Pagely\GitDeployer;

use \Monolog\LogRecord;
use \Pagely\GitDeployer\Logging\GDJsonFormatter;
use Auryn\Injector;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pagely\GitDeployer\Logging\LogContext;
use Pagely\GitDeployer\Logging\PhpStdOutLogger;
use Pagely\GitDeployer\Logging\StdOutLogFormatter;
use Psr\Log\LoggerInterface;

class Bootstrap
{
    public static function init(Injector $injector): Injector
    {
        self::injectMonolog($injector, 'git-deployer');
        $injector->share($injector);

        return $injector;
    }

    public static function injectMonolog(Injector $injector, string $loggerName = 'logger'): void
    {
        $injector->alias(LoggerInterface::class, Logger::class);
        $injector->share(LoggerInterface::class);
        $injector->delegate(Logger::class, function () use ($loggerName): Logger {
            $logger = new Logger($loggerName);
            $level = match (\strtolower((string) \getenv('LOG_LEVEL'))) {
                'error' => Logger::ERROR,
                'debug' => Logger::DEBUG,
                default => Logger::INFO,
            };

            // echo logs when testing
            if (!empty(\getenv('IS_TEST_ENVIRONMENT'))) {
                $handler = new StreamHandler('php://stdout', $level);
                $handler->setFormatter(new StdOutLogFormatter());
                $logger->pushHandler($handler);
            } else {
                $socket = "unix:///alloc/data/log.sock";
                $handler = new SocketHandler($socket, $level);
                $handler->setPersistent(true);
                $handler->setFormatter(new GDJsonFormatter());
                $logger->pushHandler($handler);
            }

            $logger->pushProcessor(function (LogRecord $record) {
                $record->extra['execution_id'] = LogContext::getExecutionId();
                return $record;
            });

            return $logger;
        });

        $injector->share(PhpStdOutLogger::class);
        $injector->delegate(PhpStdOutLogger::class, function () use ($loggerName): Logger {
            $logger = new PhpStdOutLogger($loggerName);
            $level = match (\strtolower((string) \getenv('LOG_LEVEL'))) {
                'error' => Logger::ERROR,
                'debug' => Logger::DEBUG,
                default => Logger::INFO,
            };

            $handler = new StreamHandler('php://stdout', $level);
            $handler->setFormatter(new StdOutLogFormatter());
            $logger->pushHandler($handler);

            return $logger;
        });
    }
}
