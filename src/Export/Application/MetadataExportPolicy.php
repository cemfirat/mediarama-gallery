<?php

declare(strict_types=1);

namespace Mediarama\Export\Application;

use Mediarama\Export\Domain\MetadataExportProfile;

final readonly class MetadataExportPolicy
{
    /** @param list<string> $includedFields */
    public function __construct(
        public MetadataExportProfile $profile,
        public array $includedFields = [],
    ) {
        if ($profile !== MetadataExportProfile::Custom && $includedFields !== []) {
            throw new \InvalidArgumentException('Explicit fields are only valid for the custom export profile.');
        }
    }

    public static function privacySafe(): self
    {
        return new self(MetadataExportProfile::PrivacySafe);
    }
}
