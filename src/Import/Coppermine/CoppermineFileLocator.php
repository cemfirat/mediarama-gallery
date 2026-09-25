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
        $filename = basename(str_replace('\\', '/', $filename));
        $root = rtrim($this->albumsRoot, '/');

        if ($root === '' || $filename === '' || str_contains($relative, '../')) {
            throw new \RuntimeException('Invalid Coppermine albums path configuration or source filepath.');
        }

        $candidate = $root.'/'.$relative.$filename;

        // Source rows are untrusted migration input. If the file exists, resolve
        // symlinks and require the final target to remain below the configured root.
        $realRoot = realpath($root);
        $realCandidate = realpath($candidate);

        if ($realRoot !== false && $realCandidate !== false) {
            $prefix = rtrim($realRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            if (!str_starts_with($realCandidate, $prefix)) {
                throw new \RuntimeException('Coppermine source file resolves outside the configured albums root.');
            }

            return $realCandidate;
        }

        return $candidate;
    }
}
