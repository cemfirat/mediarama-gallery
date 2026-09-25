<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

final readonly class CoppermineMediaImportReport
{
    /** @param list<string> $warnings */
    public function __construct(
        public int $imported,
        public int $skipped,
        public array $warnings,
        public bool $sourceExhausted,
    ) {
    }
}
