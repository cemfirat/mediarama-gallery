<?php

declare(strict_types=1);

namespace Mediarama\Media\Domain;

enum ProcessingState: string
{
    case Created = 'created';
    case Uploading = 'uploading';
    case Uploaded = 'uploaded';
    case Validating = 'validating';
    case Processing = 'processing';
    case Ready = 'ready';
    case Invalid = 'invalid';
    case Failed = 'failed';
}
