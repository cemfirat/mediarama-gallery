<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use DateTimeImmutable;

final readonly class InspectedMetadata
{
    /**
     * @param array<string, mixed> $embedded
     * @param list<string> $keywords
     */
    public function __construct(
        public array $embedded,
        public ?DateTimeImmutable $capturedAt = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $creator = null,
        public ?string $copyright = null,
        public ?string $cameraMake = null,
        public ?string $cameraModel = null,
        public ?string $lens = null,
        public ?int $iso = null,
        public ?string $aperture = null,
        public ?string $exposureTime = null,
        public ?string $focalLength = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $locationName = null,
        public array $keywords = [],
    ) {
    }
}
