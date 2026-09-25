<?php

declare(strict_types=1);

namespace Mediarama\Export\Application;

use Mediarama\Media\Domain\MediaAsset;

interface MetadataWriter
{
    public function supports(string $mimeType): bool;

    /**
     * Writes an export copy. Implementations must never mutate the immutable
     * Mediarama original.
     *
     * @param resource $source
     * @return resource
     */
    public function write($source, MediaAsset $media, MetadataExportPolicy $policy);
}
