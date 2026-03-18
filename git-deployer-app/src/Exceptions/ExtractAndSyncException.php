<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Exceptions;

use \Exception;

final class ExtractAndSyncException extends GitDeployerException
{
    public static function ErrorWhileUntar(Exception $e): static
    {
        return new static("Error while untar the file.", 500, $e);
    }

    public static function unHealthyWordpress(string $message): static
    {
        return new static($message, 500);
    }
}
