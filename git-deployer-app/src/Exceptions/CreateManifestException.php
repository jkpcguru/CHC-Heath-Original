<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Exceptions;

use \Exception;

final class CreateManifestException extends GitDeployerException
{
    public static function ErrorWhileReadingTar(Exception $e): static
    {
        return new static("Error while extracting the tar file.".$e->getMessage() , 500, $e);
    }
}
