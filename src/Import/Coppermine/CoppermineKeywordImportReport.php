<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

final readonly class CoppermineKeywordImportReport
{
    /** @param list<string> $unmappedPictureIds */
    public function __construct(
        public int $createdTags,
        public int $createdLinks,
        public array $unmappedPictureIds,
        public string $separator,
    ) {
    }
}
