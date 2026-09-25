<?php

declare(strict_types=1);

namespace Mediarama\Media\Domain;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Document = 'document';
}
