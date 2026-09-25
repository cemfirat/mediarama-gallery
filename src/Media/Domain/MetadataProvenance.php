<?php

declare(strict_types=1);

namespace Mediarama\Media\Domain;

enum MetadataProvenance: string
{
    case Embedded = 'embedded';
    case CoppermineImport = 'coppermine_import';
    case Migration = 'migration';
    case User = 'user';
    case Automated = 'automated';
}
