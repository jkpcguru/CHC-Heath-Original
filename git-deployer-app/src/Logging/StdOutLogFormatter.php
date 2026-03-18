<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class StdOutLogFormatter extends JsonFormatter
{
    public function format(LogRecord $record): string
    {
        $extraStr = empty($record->extra) ? '' : \json_encode($record->extra);

        return $record->level->getName() .': '.$record->message.' '. $extraStr. ($this->appendNewline ? "\n" : '');
    }
}
