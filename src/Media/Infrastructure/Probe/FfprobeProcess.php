<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Probe;

use JsonException;
use Symfony\Component\Process\Process;

final readonly class FfprobeProcess
{
    public function __construct(
        private string $binary = 'ffprobe',
        private float $timeoutSeconds = 30.0,
        private int $probeSizeBytes = 33554432,
        private int $analyzeDurationMicroseconds = 5000000,
    ) {
        if ($this->timeoutSeconds <= 0) {
            throw new \InvalidArgumentException('FFprobe process timeout must be positive.');
        }
        if ($this->probeSizeBytes < 32) {
            throw new \InvalidArgumentException('FFprobe probe size must be at least 32 bytes.');
        }
        if ($this->analyzeDurationMicroseconds < 0) {
            throw new \InvalidArgumentException('FFprobe analyze duration must not be negative.');
        }
    }

    /** @return list<string> */
    public function streamTypes(string $path): array
    {
        $process = new Process([
            $this->binary,
            '-v', 'error',
            '-probesize', (string) $this->probeSizeBytes,
            '-analyzeduration', (string) $this->analyzeDurationMicroseconds,
            '-show_entries', 'stream=codec_type',
            '-of', 'json=c=1',
            $path,
        ]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (!$process->isSuccessful()) {
            $details = trim($process->getErrorOutput());
            if ($details === '') {
                $details = trim($process->getOutput());
            }

            throw new \RuntimeException(sprintf(
                'FFprobe failed with exit code %s%s',
                (string) $process->getExitCode(),
                $details === '' ? '.' : ': '.substr($details, 0, 2000),
            ));
        }

        try {
            $decoded = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new \RuntimeException('FFprobe returned invalid JSON.', 0, $error);
        }

        if (!is_array($decoded) || !isset($decoded['streams']) || !is_array($decoded['streams'])) {
            throw new \RuntimeException('FFprobe response does not contain a streams array.');
        }

        $types = [];
        foreach ($decoded['streams'] as $stream) {
            if (!is_array($stream)) {
                continue;
            }

            $type = $stream['codec_type'] ?? null;
            if (is_string($type) && $type !== '') {
                $types[$type] = true;
            }
        }

        return array_keys($types);
    }
}
