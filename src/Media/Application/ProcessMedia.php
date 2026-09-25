<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Shared\Application\AsyncMessage;

final readonly class ProcessMedia implements AsyncMessage
{
    public function __construct(public string $mediaId)
    {
    }
}
