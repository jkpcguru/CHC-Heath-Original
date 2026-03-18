<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Commands;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TestCommand extends Command
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
        parent::__construct('test');
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('test')
            ->addOption('test-log', null, InputOption::VALUE_NONE, "Test log")
            ->addOption('test-env', null, InputOption::VALUE_NONE, "Print all environment variables")
            ->setDescription('Test command');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $testLogs = $input->getOption('test-log');
        if ($testLogs) {
            echo "Testing Logs";
            $this->logger->info("Test command Info message");
            $this->logger->debug("This is a debug message");
            $this->logger->error("Something went wrong ERROR message");
            $this->logger->warning("This is warning message");
            $this->logger->emergency("Something is emergency");
        }

        $testEnv = $input->getOption('test-env');
        if ($testEnv) {
            echo "Testing ENV variables";
            \print_r(\getenv());
        }

        return Command::SUCCESS;
    }
}
