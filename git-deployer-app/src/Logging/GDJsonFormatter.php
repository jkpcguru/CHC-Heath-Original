<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class GDJsonFormatter extends JsonFormatter
{
    public function format(LogRecord $record): string
    {
        $record = ['gdlogdata' => $record];
        return $this->toJson($this->normalize($record), true) . ($this->appendNewline ? "\n" : '');
    }
}
