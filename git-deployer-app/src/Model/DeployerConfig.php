<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Model;

final class DeployerConfig
{
    use ModelTrait;

    public readonly ?bool $skip;
    public readonly ?string $statusJobId;
    public readonly string $tarFile;
    public readonly string $deployDir;
    public readonly ?string $postDeployCmd;
    private bool $isWPHealthyPreDeploy;
    private bool $isWPHealthyPostDeploy;
    public readonly bool $healthCheck;

    /**
     * @return array{skip: bool,statusJobId: string|null,tarFile: string,deployDir: string,postDeployCmd: string|null,healthCheck: bool,isWPHealthyPreDeploy: bool,isWPHealthyPostDeploy: bool}
     */
    protected function getDefaults(): array
    {
        return [
            'skip' => false,
            'statusJobId' => null,
            'tarFile' => '',
            'deployDir' => '',
            'postDeployCmd' => null,
            'healthCheck' => true,
            'isWPHealthyPreDeploy' => true,
            'isWPHealthyPostDeploy' => true,
        ];
    }

    public function setWPHealthPreDeploy(bool $isHealthy): void
    {
        $this->isWPHealthyPreDeploy = $isHealthy;
    }

    public function isWPHealthyPreDeploy(): bool
    {
        return $this->isWPHealthyPreDeploy;
    }

    public function setWPHealthPostDeploy(bool $isHealthy): void
    {
        $this->isWPHealthyPostDeploy = $isHealthy;
    }

    public function isWPHealthyPosyDeploy(): bool
    {
        return $this->isWPHealthyPostDeploy;
    }
}
