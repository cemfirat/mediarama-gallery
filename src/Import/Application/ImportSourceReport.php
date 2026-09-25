<?php

declare(strict_types=1);

namespace Mediarama\Import\Application;

final readonly class ImportSourceReport
{
    /** @param array<string,int> $counts
     *  @param list<string> $warnings
     */
    public function __construct(
        public string $source,
        public string $detectedVersion,
        public array $counts,
        public array $warnings = [],
    ) {
    }
}
