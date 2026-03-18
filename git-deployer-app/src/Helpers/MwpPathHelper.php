<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Helpers;

use Pagely\GitDeployer\Config\MwpConfig;
use Pagely\GitDeployer\Model\DeployerConfig;

/****
 * No trailing slash at end of the path
 */
class MwpPathHelper
{
    public function __construct(
        private readonly string $deployerRootDir = MwpConfig::DEPLOYER_ROOT_DIR,
        private readonly string $wordpressRootDir = MwpConfig::WP_ROOT_DIR,
    ) {
    }

    public function getDeployerRootDir(): string
    {
        return $this->deployerRootDir;
    }

    public function getWordpressRootDir(): string
    {
        return $this->wordpressRootDir;
    }

    public function getTarFilePath(DeployerConfig $config): string
    {
        return $this->deployerRootDir.'/'.$config->tarFile;
    }

    public function getDeployTempDirPath(DeployerConfig $config): string
    {
        $extractDir = \preg_replace('/[^A-Za-z0-9\-_]/', '-', $config->tarFile);
        $extractDir = \substr($extractDir ?? '', 0, 50);
        return $this->deployerRootDir .'/'. $extractDir;
    }

    public function getTarExtractPath(DeployerConfig $config): string
    {
        return $this->getDeployTempDirPath($config).'/repo';
    }

    public function getTarExtractBackupDirPath(DeployerConfig $config): string
    {
        return $this->getDeployTempDirPath($config).'/backup';
    }

    public function getManifestFilePath(): string
    {
        return $this->deployerRootDir.'/'.MwpConfig::MANIFEST_FILE_NAME;
    }
}
