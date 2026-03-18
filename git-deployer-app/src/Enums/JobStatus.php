<?php declare(strict_types=1);

namespace Pagely\GitDeployer\Enums;

enum JobStatus: string
{
    case Failure = 'FAILURE';
    case Success = 'SUCCESS';
    case Pending = 'PENDING';
    case Running = 'RUNNING';
    case Aborted = 'ABORTED';
    case Stalled = 'STALLED';
    case ActionRequired = 'ACTION_REQUIRED';
    case Queued = 'QUEUED';
    case NA = 'NA';
}
