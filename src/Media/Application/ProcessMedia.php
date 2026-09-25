<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: 'async')]
final readonly class ProcessMedia
{
    public function __construct(public string $mediaId)
    {
    }
}
