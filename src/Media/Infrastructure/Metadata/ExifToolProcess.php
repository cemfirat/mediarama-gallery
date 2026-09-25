<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Metadata;

use Symfony\Component\Process\Process;

final readonly class ExifToolProcess
{
    public function __construct(
        private string $binary = 'exiftool',
        private float $timeoutSeconds = 30.0,
    ) {
    }

    /**
     * @param list<string> $arguments
     */
    public function run(array $arguments): string
    {
        $process = new Process([$this->binary, ...$arguments]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf(
                'ExifTool failed with exit code %s: %s',
                (string) $process->getExitCode(),
                trim($process->getErrorOutput()),
            ));
        }

        return $process->getOutput();
    }
}
