<?php

declare(strict_types=1);

namespace Mediarama\Export\Domain;

enum MetadataExportProfile: string
{
    case Original = 'original';
    case Current = 'current';
    case PrivacySafe = 'privacy_safe';
    case Custom = 'custom';
}
