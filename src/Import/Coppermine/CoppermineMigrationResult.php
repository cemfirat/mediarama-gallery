<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Symfony\Component\Uid\Uuid;

final readonly class CoppermineMigrationResult
{
    public function __construct(
        public Uuid $runId,
        public CoppermineReconciliationReport $reconciliation,
        public CoppermineInteractionImportReport $interactions,
    ) {
    }
}
