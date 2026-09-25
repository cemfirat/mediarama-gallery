<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Domain\MetadataProvenance;

final readonly class ApplyInspectedMetadata
{
    public function __invoke(MediaAsset $media, InspectedMetadata $metadata): void
    {
        $media->applyEmbeddedMetadata(
            $metadata->embedded,
            [
                'title' => $metadata->title,
                'description' => $metadata->description,
                'captured_at' => $metadata->capturedAt,
                'creator' => $metadata->creator,
                'copyright' => $metadata->copyright,
                'camera_make' => $metadata->cameraMake,
                'camera_model' => $metadata->cameraModel,
                'lens' => $metadata->lens,
                'iso' => $metadata->iso,
                'aperture' => $metadata->aperture,
                'exposure_time' => $metadata->exposureTime,
                'focal_length' => $metadata->focalLength,
                'latitude' => $metadata->latitude,
                'longitude' => $metadata->longitude,
                'location_name' => $metadata->locationName,
            ],
            MetadataProvenance::Embedded,
        );
    }
}
