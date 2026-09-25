<?php

declare(strict_types=1);

namespace Mediarama\Import\Application;

interface ImportSource
{
    public function name(): string;

    public function inspect(): ImportSourceReport;
}
