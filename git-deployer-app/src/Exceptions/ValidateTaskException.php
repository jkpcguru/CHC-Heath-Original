<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Exceptions;

final class ValidateTaskException extends GitDeployerException
{
    public static function tarDoesNotExist(string $name): static
    {
        return new static("Deployment Tar file '{$name}' does not exist", 500);
    }

    public static function deployDestDoesNotExist(string $path): static
    {
        return new static("Deployment destination path '{$path}' does not exist", 500);
    }
}
