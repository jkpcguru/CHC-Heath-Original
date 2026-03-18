<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Services\MwpTasks;

use AFM\Rsync\Rsync;
use Pagely\GitDeployer\Config\MwpConfig;
use Pagely\GitDeployer\Exceptions\ExtractAndSyncException;
use Pagely\GitDeployer\Helpers\MwpPathHelper;
use Pagely\GitDeployer\Helpers\ShellCommandHelper;
use Pagely\GitDeployer\Logging\PhpStdOutLogger;
use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Task\AbstractTask;
use Psr\Log\LoggerInterface;

class ExtractAndSyncTask extends AbstractTask
{
    private const MODE_RENAME = 'rename';
    private const MODE_DELETE = 'delete';
    private const MODE_ROLLBACK = 'rollback';

    /** @var array<string> $deletedFileList */
    private array $deletedFileList;

    /** @var array<string> $manifestFiles */
    private array $manifestFiles;

    /** @var array<string, mixed> $newlyCreatedFiles */
    private array $newlyCreatedFiles = [];

    public function __construct(
        LoggerInterface         $logger,
        private MwpPathHelper   $pathHelper,
        private PhpStdOutLogger $printLogger,
    ) {
        parent::__construct($logger, $printLogger);
    }

    public function name(): string
    {
        return "File Sync Task";
    }

    /**
     * @throws ExtractAndSyncException
     * @throws \Exception
     */
    protected function exec(DeployerConfig $config): void
    {
        $tarExtractDir = $this->pathHelper->getTarExtractPath($config);
        $this->printLogger->info("Syncing uploaded files...");
        $this->syncFiles($config, $tarExtractDir);

        if (!$this->isWPHealthy($config)) {
            $errorMessage = "Wordpress is not healthy post file sync, reverting changes";
            $this->logger->error($errorMessage);
            $this->printLogger->error($errorMessage);
            $this->rollbackFileSync($config);
            $this->printLogger->error("Changes reverted.");
            throw ExtractAndSyncException::unHealthyWordpress("Unhealthy Wordpress after file sync.");
        }

        if (!$this->shouldSkipDeleteSync($config)) {
            $this->syncDeletedFiles($config);
        }

        $this->printLogger->info("file sync done.");
    }

    /**
     * @throws \Exception
     */
    private function syncFiles(DeployerConfig $config, string $sourceDir): void
    {
        $sourceDir = $sourceDir.'/';
        $destDir = $config->deployDir.'/';
        $backupDir = $this->pathHelper->getTarExtractBackupDirPath($config).'/';
        $rsyncConfig = [
            "exclude" => \array_map(fn ($item) => '/'.$item, MwpConfig::WP_IGNORE_FILE),
            "verbose" => true,
        ];

        $this->logger->info("Syncing files. Source: {$sourceDir} destination: {$destDir}");

        $rsync = new Rsync($rsyncConfig);
        $command = $rsync->getCommand($sourceDir, $destDir);
        $command->addArgument("checksum");
        $command->addArgument("backup");
        $command->addArgument("backup-dir", $backupDir);
        $command->addArgument("suffix", "");
        // Capture file changes for extract new files
        $command->addArgument("itemize-changes");
        $command->addArgument("out-format='%i %n'");
        $command->addArgument("omit-dir-times");

        $command->addArgument("no-perms");
        $command->addArgument("no-owner");
        $command->addArgument("no-group");

        // execute echo the command output so capturing output via output buffer
        \ob_start();
        $command->execute(true);
        /** @var string $output */
        $output = \ob_get_clean();

        $this->logger->info("Syncing complete");
        $this->newlyCreatedFiles = $this->filterNewlyCreatedFiles($output);
        $this->logger->info("Newly created files", [
            'files' => \implode(", ", $this->newlyCreatedFiles)
        ]);
    }

    /**
     * @return array<string> An indexed array of newly created file paths, with the prefix removed.
     */
    private function filterNewlyCreatedFiles(string $output): array
    {
        // rsync output `>f+++++++++` at start of the line for new files
        $newFilePrefix = '>f+++++++++ ';
        return \array_values(\array_map(
            function ($line) use ($newFilePrefix) {
                return \str_replace($newFilePrefix, '', $line);
            },
            \array_filter(
                \explode(PHP_EOL, $output),
                fn ($line) => \str_starts_with($line, $newFilePrefix)
            )
        ));
    }

    private function rollbackFileSync(DeployerConfig $config): void
    {
        $sourceDir = $this->pathHelper->getTarExtractBackupDirPath($config).'/';
        $destDir = $config->deployDir.'/';
        $rsyncConfig = [
            "exclude" => \array_map(fn ($item) => '/'.$item, MwpConfig::WP_IGNORE_FILE),
            "verbose" => true,
        ];

        if (\file_exists($sourceDir)) {
            $this->logger->info("Syncing backup files back. source: {$sourceDir} destination: {$destDir}");

            $rsync = new Rsync($rsyncConfig);
            $command = $rsync->getCommand($sourceDir, $destDir);
            $command->execute();
            $this->logger->info("Backup restored.");
        } else {
            $this->logger->info("No backup dir exist to restore. source: {$sourceDir}");
        }

        // Deleting newly created files
        if (!empty($this->newlyCreatedFiles)) {
            $deletedFilePrefix = \rtrim($config->deployDir, '/') . '/';

            // Build the delete command efficiently
            $deleteStr = \implode(
                ' ',
                \array_map(
                    fn ($file) =>
                    \escapeshellarg($deletedFilePrefix . $file),
                    $this->newlyCreatedFiles
                )
            );

            // Execute delete command in a single shell call
            ShellCommandHelper::deleteFile($deleteStr);
        }
    }

    protected function isWPHealthy(DeployerConfig $config): bool
    {
        // return healthy if healthCheck is false
        if (!$config->healthCheck) {
            return true;
        }

        // if wordpress is not healthy before the deployment start, no need to check wordpress health
        if (!$config->isWPHealthyPreDeploy()) {
            return true;
        }

        return ShellCommandHelper::isWordPressHealthy($this->pathHelper->getWordpressRootDir());
    }

    protected function shouldSkip(DeployerConfig $config): bool
    {
        return false;
    }

    private function syncDeletedFiles(DeployerConfig $config): void
    {
        // Rename files before proceeding
        $this->processFileDeletion($config, $this->deletedFileList, $this->manifestFiles, self::MODE_RENAME);

        if ($this->isWPHealthy($config)) {
            // delete actual files if wordpress is healthy
            $this->processFileDeletion($config, $this->deletedFileList, $this->manifestFiles, self::MODE_DELETE);
        }
        // rollback deleted files and replaced files.
        else {
            $errorMessage = "Reverting delete file sync, as wordpress is not healthy.";
            $this->logger->error($errorMessage);
            $this->printLogger->error($errorMessage);
            // rollback deleted files
            $this->processFileDeletion($config, $this->deletedFileList, $this->manifestFiles, self::MODE_ROLLBACK);
            $this->rollbackFileSync($config);
            throw ExtractAndSyncException::unHealthyWordpress("Unhealthy Wordpress after file sync while deleting file.");
        }
    }

    /**
     * @param array<string> $deletedFileList
     * @param array<string> $manifest
     */
    private function processFileDeletion(DeployerConfig $config, array $deletedFileList, array $manifest, string $mode): void
    {
        $rootDir = $config->deployDir . '/';
        $skippedFiles = [];
        $deletedFiles = [];
        $backupSuffix = '.bak';

        $this->logger->info("Running file deletion with Mode: $mode");
        foreach ($deletedFileList as $deletedFile) {
            $relativePath = \trim($deletedFile, ' /');
            $path = $rootDir . $relativePath;
            $backupPath = $path . $backupSuffix;

            // Skip if not in manifest (safety check)
            if (!isset($manifest[$path])) {
                $skippedFiles[] = $path;
                continue;
            }

            try {
                switch ($mode) {
                    case self::MODE_RENAME:
                        if (@\rename($path, $backupPath)) {
                            $deletedFiles[] = $path;
                        } else {
                            $skippedFiles[] = $path;
                        }
                        break;

                    case self::MODE_ROLLBACK:
                        if (@\rename($backupPath, $path)) {
                            $deletedFiles[] = $path;
                        } else {
                            $skippedFiles[] = $backupPath;
                        }
                        break;

                    case self::MODE_DELETE:
                        $this->handleDeletion($path, $backupPath, $deletedFiles, $skippedFiles);
                        break;

                    default:
                        $this->logger->warning("Unknown deletion mode: $mode");
                        break;
                }
            } catch (\Throwable $e) {
                $this->logger->warning("Exception during deletion for: $path. Error: " . $e->getMessage());
                $skippedFiles[] = $path;
            }
        }

        $this->logger->info("$mode files detail:", [
            'deleted' => \implode(', ', $deletedFiles),
            'skipped' => \implode(', ', $skippedFiles),
        ]);
    }

    /**
     * Handle the deletion of files and directories.
     *
     * @param string $path Path to the original file
     * @param string $backupPath Path to the backup file
     * @param array<int, string> $deletedFiles List of successfully deleted files
     * @param array<int, string> $skippedFiles List of files that could not be deleted
     */
    private function handleDeletion(string $path, string $backupPath, array &$deletedFiles, array &$skippedFiles): void
    {
        $deleted = false;

        // Handle directories via shell helper
        if (\is_dir($path) || \is_dir($backupPath)) {
            ShellCommandHelper::deleteFile(\escapeshellarg($backupPath) . ' ' . \escapeshellarg($path));
            $deletedFiles[] = $path;
            $deleted = true;
        } else {
            foreach ([$path, $backupPath] as $target) {
                if (\file_exists($target) && @\unlink($target)) {
                    $deleted = true;
                    $deletedFiles[] = $target;
                }
            }
        }

        if (!$deleted) {
            $skippedFiles[] = $path;
        }
    }

    protected function shouldSkipDeleteSync(DeployerConfig $config): bool
    {
        $sourceDir = $this->pathHelper->getTarExtractPath($config);
        $deleteFilePath = $sourceDir . '/' . MwpConfig::DELETED_FILES_NAME;

        // Check if the deleted files list exists
        if (!\file_exists($deleteFilePath)) {
            $this->logger->info("Skipping file deletion as no " . MwpConfig::DELETED_FILES_NAME . " file found.");
            return true;
        }

        // Load the list of files to delete
        $deletedFileList = \file($deleteFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($deletedFileList)) {
            $this->logger->info("No files added in the deleted list, skipping task.");
            return true;
        }

        // Load and decode the manifest file
        $manifestFile = $this->pathHelper->getManifestFilePath();
        $manifestContent = @\file_get_contents($manifestFile);
        if ($manifestContent === false) {
            $this->logger->warning("Failed to read the manifest file . {$manifestFile}, skipping delete sync.");
            return true;
        }

        $manifest = \json_decode($manifestContent, true);
        if (\json_last_error() !== JSON_ERROR_NONE || !\is_array($manifest)) {
            $this->logger->warning("Invalid JSON format in the manifest file: {$manifestFile}, skipping delete sync.");
            return true;
        }

        // Cache deleted files and manifest for reuse
        $this->deletedFileList = $deletedFileList;
        $this->manifestFiles = \array_flip($manifest);

        return false;
    }
}
