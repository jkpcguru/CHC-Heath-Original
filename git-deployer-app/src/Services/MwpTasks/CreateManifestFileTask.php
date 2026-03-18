<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Services\MwpTasks;

use Pagely\GitDeployer\Config\MwpConfig;
use Pagely\GitDeployer\Exceptions\CreateManifestException;
use Pagely\GitDeployer\Exceptions\ExtractAndSyncException;
use Pagely\GitDeployer\Helpers\MwpPathHelper;
use Pagely\GitDeployer\Helpers\ShellCommandHelper;
use Pagely\GitDeployer\Logging\PhpStdOutLogger;
use Pagely\GitDeployer\Model\DeployerConfig;
use Pagely\GitDeployer\Task\AbstractTask;
use Psr\Log\LoggerInterface;

final class CreateManifestFileTask extends AbstractTask
{
    public function __construct(
        LoggerInterface         $logger,
        private MwpPathHelper   $pathHelper,
        private PhpStdOutLogger $printLogger,
    ) {
        parent::__construct($logger, $printLogger);
    }

    /**
     * @throws CreateManifestException
     * @throws ExtractAndSyncException
     */
    protected function exec(DeployerConfig $config): void
    {
        $this->printLogger->info("Creating files map...");

        $manifestFilePath = $this->pathHelper->getManifestFilePath();
        $this->createManifestFile($manifestFilePath);

        // create temp deploy dir
        $tempDeployDir = $this->pathHelper->getDeployTempDirPath($config);
        if (\file_exists($tempDeployDir)) {
            $this->logger->info("Removed existing dir: ". $tempDeployDir);
            ShellCommandHelper::deleteFile($tempDeployDir);
        }
        \mkdir($tempDeployDir);

        // extract tar file to temp deploy dir
        $tarExtractDir = $this->pathHelper->getTarExtractPath($config);
        $this->extractTar($config, $tarExtractDir);

        $tarFileList = $this->getFilesListFromTar($config, $tarExtractDir);
        /** @var string $fileContent */
        $fileContent = \file_get_contents($manifestFilePath);
        /** @var array<string> $existingFileList */
        $existingFileList = \json_decode($fileContent, true);
        $manifestFiles =  \array_values(
            \array_unique(
                \array_merge($existingFileList, $tarFileList)
            )
        );

        \file_put_contents($manifestFilePath, \json_encode($manifestFiles));

        $this->printLogger->info("file map Created.");
    }

    private function createManifestFile(string $file): void
    {
        if (!\file_exists($file)) {
            \file_put_contents($file, "[]");
        }
    }

    /**
     * @return array<int, string>
     * @throws CreateManifestException
     */
    private function getFilesListFromTar(DeployerConfig $config, string $tarExtractDir): array
    {
        $files = [];
        $ignoreList = $this->prepareIgnoreList();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tarExtractDir),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            // Generate relative file path from extract directory
            $filePath = \ltrim(\str_replace($tarExtractDir . '/', '', $file->getPathname()), '/');

            // Check against ignore list before adding
            if (
                isset($ignoreList['files'][$filePath]) ||
                $this->isPathInIgnoreDirs($filePath, $ignoreList['dirs'])
            ) {
                continue;
            }

            $wpFilePath = $config->deployDir . '/' . $filePath;

            if ($file->isFile()) {
                $files[] = $wpFilePath;
            }
            // if dir is created by CI/CD add that dir name in manifest.json
            elseif (
                $file->isDir() &&
                !\str_ends_with($filePath, '..') &&
                !\file_exists($wpFilePath)
            ) {
                $files[] = \rtrim($wpFilePath, '/.');
            }
        }

        return $files;
    }

    private function extractTar(DeployerConfig $config, string $tarExtractDir): void
    {
        $this->logger->info("Extracting tar files at: ". $tarExtractDir);
        $tarFile = $this->pathHelper->getTarFilePath($config);

        try {
            \mkdir($tarExtractDir);
            $phar = new \PharData($tarFile);
            $phar->extractTo($tarExtractDir);
        } catch (\Exception $e) {
            $this->logger->error("Error while untar the file", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->printLogger->error("Unknown error while extracting tar file");
            throw CreateManifestException::ErrorWhileReadingTar($e);
        }
    }

    /**
     * @return array{
     *     files: array<string, bool>,
     *     dirs: array<string>
     * }
     */
    private function prepareIgnoreList(): array
    {
        $files = [];
        $dirs = [];

        foreach (MwpConfig::WP_IGNORE_FILE as $ignoreItem) {
            if (\str_ends_with($ignoreItem, '/')) {
                $dirs[] = \rtrim($ignoreItem, '/');
            } else {
                $files[$ignoreItem] = true;
            }
        }

        return ['files' => $files, 'dirs' => $dirs];
    }

    /**
     * @param array<string> $ignoreDirs
     *
     */
    private function isPathInIgnoreDirs(string $filePath, array $ignoreDirs): bool
    {
        foreach ($ignoreDirs as $dir) {
            if (\str_starts_with($filePath, $dir . '/')) {
                return true;
            }
        }

        return false;
    }

    public function name(): string
    {
        return "Create Manifest File";
    }

    protected function shouldSkip(DeployerConfig $config): bool
    {
        return false;
    }
}
