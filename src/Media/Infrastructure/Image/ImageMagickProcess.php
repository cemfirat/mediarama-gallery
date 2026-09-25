<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Image;

use Symfony\Component\Process\Process;

final readonly class ImageMagickProcess
{
    public function __construct(
        private string $binary = 'magick',
        private float $timeoutSeconds = 60.0,
    ) {
    }

    /** @param list<string> $arguments */
    public function run(array $arguments): void
    {
        $process = new Process([$this->binary, ...$arguments]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                'ImageMagick failed with exit code %s: %s',
                (string) $process->getExitCode(),
                trim($process->getErrorOutput()),
            ));
        }
    }
}
