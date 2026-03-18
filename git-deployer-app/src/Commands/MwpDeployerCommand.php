<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Commands;

use Pagely\GitDeployer\Helpers\MwpPathHelper;
use Pagely\GitDeployer\Services\MwpDeployer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MwpDeployerCommand extends Command
{
    public function __construct(
        private MwpDeployer $deployer,
        private MwpPathHelper $pathHelper,
    ) {
        parent::__construct('mwp:deployer');
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('mwp:deployer')
            ->setDescription('Extract uploaded tar file and other deployments stuff')
            ->addArgument('tarFile', InputArgument::REQUIRED, 'Tar file path')
            ->addOption('destDir', null, InputOption::VALUE_OPTIONAL, 'Deployment destination directory')
            ->addOption('skipHealthCheck', null, InputOption::VALUE_NONE, 'Don\'t Check WordPress health after deployment and roll back if it fails.')
            ->addOption('postDeploymentCommand', null, InputOption::VALUE_OPTIONAL, 'Commands to execute on the server after the deployment is complete.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $tarFilePath */
        $tarFilePath = $input->getArgument('tarFile');
        /** @var string $destDir */
        $destDir = $input->getOption('destDir');
        $skipHealthCheck = (bool) $input->getOption('skipHealthCheck');

        /** @var string $postDeploymentCommand */
        $postDeploymentCommand = $input->getOption('postDeploymentCommand');
        $wpRoot = $this->pathHelper->getWordpressRootDir();

        // Set destination directory
        $destDir = empty($destDir) || $destDir === '.' ? $wpRoot : $wpRoot ."/". \trim($destDir, ' /');

        // Execute deployment
        try {
            $this->deployer->run(
                tarFile: $tarFilePath,
                deployDir: $destDir,
                healthCheck: !$skipHealthCheck,
                postDeployCmd: $postDeploymentCommand
            );
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            return Command::FAILURE;
        }
    }
}
