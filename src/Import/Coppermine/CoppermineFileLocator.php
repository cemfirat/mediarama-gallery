<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

final readonly class CoppermineFileLocator
{
    public function __construct(private string $albumsRoot)
    {
    }

    public function locate(string $filepath, string $filename): string
    {
        $relative = ltrim(str_replace('\\', '/', $filepath), '/');
        $filename = basename($filename);
        $root = rtrim($this->albumsRoot, '/');

        if ($root === '' || str_contains($relative, '../')) {
            throw new \RuntimeException('Invalid Coppermine albums path configuration or source filepath.');
        }

        return $root.'/'.$relative.$filename;
    }
}
