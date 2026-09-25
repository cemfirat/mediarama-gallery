<?php

declare(strict_types=1);

namespace Mediarama\Upload\Domain;

enum UploadStatus: string
{
    case Created = 'created';
    case Uploading = 'uploading';
    case Uploaded = 'uploaded';
    case Finalizing = 'finalizing';
    case Completed = 'completed';
    case Expired = 'expired';
    case Failed = 'failed';
}
