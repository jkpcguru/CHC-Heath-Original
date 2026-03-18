<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Model;

use DateTimeInterface;

class TaskResult
{
    use ModelTrait;

    public readonly string $name;
    public readonly DateTimeInterface $start;
    public readonly DateTimeInterface $stop;
    public readonly bool $ran;
    public readonly bool $success;
    public readonly ?string $message;
}
