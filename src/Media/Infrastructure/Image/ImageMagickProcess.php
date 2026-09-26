<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Image;

use Symfony\Component\Process\Process;

final readonly class ImageMagickProcess
{
    public function __construct(
        private ImageMagickResourceLimits $limits,
        private string $binary = 'magick',
        private string $identifyBinary = 'identify',
        private float $timeoutSeconds = 60.0,
    ) {
        if ($this->timeoutSeconds <= 0) {
            throw new \InvalidArgumentException('ImageMagick process timeout must be positive.');
        }
    }

    /** @param list<string> $arguments */
    public function convert(array $arguments): string
    {
        return $this->run($this->binary, 'convert', $arguments);
    }

    /** @param list<string> $arguments */
    public function identify(array $arguments): string
    {
        return $this->run($this->identifyBinary, 'identify', $arguments);
    }

    /** @param list<string> $arguments */
    private function run(string $binary, string $operation, array $arguments): string
    {
        $process = new Process(
            [$binary, ...$this->limits->commandArguments(), ...$arguments],
            null,
            $this->limits->environment(),
        );
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (!$process->isSuccessful()) {
            $details = trim($process->getErrorOutput());
            if ($details === '') {
                $details = trim($process->getOutput());
            }

            throw new \RuntimeException(sprintf(
                'ImageMagick %s failed with exit code %s%s',
                $operation,
                (string) $process->getExitCode(),
                $details === '' ? '.' : ': '.$details,
            ));
        }

        return $process->getOutput();
    }
}
