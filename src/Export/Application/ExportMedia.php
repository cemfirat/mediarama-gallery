<?php

declare(strict_types=1);

namespace Mediarama\Export\Application;

use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Export\Domain\MetadataExportProfile;

final readonly class ExportMedia
{
    public function __construct(
        private MediaStorage $storage,
        private MetadataWriter $writer,
    ) {
    }

    /** @return resource */
    public function __invoke(MediaAsset $media, MetadataExportPolicy $policy)
    {
        $source = $this->storage->read($media->original);

        if ($policy->profile === MetadataExportProfile::Original) {
            return $source;
        }

        if (!$this->writer->supports($media->mimeType)) {
            throw new \DomainException(sprintf(
                'Metadata export is not supported for MIME type "%s".',
                $media->mimeType,
            ));
        }

        return $this->writer->write($source, $media, $policy);
    }
}
