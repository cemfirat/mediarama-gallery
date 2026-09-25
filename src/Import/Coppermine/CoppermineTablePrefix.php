<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

final readonly class CoppermineTablePrefix
{
    public function __construct(public string $value)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $value)) {
            throw new \InvalidArgumentException('Invalid Coppermine table prefix.');
        }
    }

    public function table(string $suffix): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $suffix)) {
            throw new \InvalidArgumentException('Invalid Coppermine table suffix.');
        }

        return $this->value.$suffix;
    }
}
