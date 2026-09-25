<?php

declare(strict_types=1);

namespace Mediarama\Export\Infrastructure;

use Mediarama\Export\Application\MetadataExportPolicy;
use Mediarama\Export\Application\MetadataWriter;
use Mediarama\Export\Domain\MetadataExportProfile;
use Mediarama\Media\Domain\MediaAsset;
use Mediarama\Media\Infrastructure\Metadata\ExifToolProcess;

final readonly class ExifToolMetadataWriter implements MetadataWriter
{
    private const SUPPORTED_MIME_TYPES = [
        'image/jpeg',
        'image/tiff',
        'image/png',
        'image/webp',
        'image/avif',
        'image/heic',
        'image/heif',
    ];

    public function __construct(private ExifToolProcess $process)
    {
    }

    public function supports(string $mimeType): bool
    {
        return in_array(strtolower($mimeType), self::SUPPORTED_MIME_TYPES, true);
    }

    public function write($source, MediaAsset $media, MetadataExportPolicy $policy)
    {
        if (!is_resource($source)) {
            throw new \InvalidArgumentException('Metadata source must be a readable stream.');
        }

        $extension = $this->extensionFor($media->mimeType);
        $input = tempnam(sys_get_temp_dir(), 'mediarama-meta-in-');
        $output = tempnam(sys_get_temp_dir(), 'mediarama-meta-out-');

        if ($input === false || $output === false) {
            throw new \RuntimeException('Unable to allocate metadata export temporary files.');
        }

        $inputWithExtension = $input.'.'.$extension;
        $outputWithExtension = $output.'.'.$extension;

        try {
            rename($input, $inputWithExtension);
            rename($output, $outputWithExtension);

            $target = fopen($inputWithExtension, 'wb');
            if ($target === false) {
                throw new \RuntimeException('Unable to open metadata export input.');
            }
            stream_copy_to_stream($source, $target);
            fclose($target);

            copy($inputWithExtension, $outputWithExtension);

            $arguments = [
                '-overwrite_original',
                ...$this->metadataArguments($media, $policy),
                '--',
                $outputWithExtension,
            ];

            $this->process->run($arguments);

            $result = fopen($outputWithExtension, 'rb');
            if ($result === false) {
                throw new \RuntimeException('Unable to open generated metadata export.');
            }

            // Keep the temporary file alive until the returned stream is closed by
            // copying it into an anonymous temporary stream.
            $stream = fopen('php://temp', 'w+b');
            stream_copy_to_stream($result, $stream);
            fclose($result);
            rewind($stream);

            return $stream;
        } finally {
            @unlink($input);
            @unlink($output);
            @unlink($inputWithExtension);
            @unlink($outputWithExtension);
        }
    }

    /** @return list<string> */
    private function metadataArguments(MediaAsset $media, MetadataExportPolicy $policy): array
    {
        if ($policy->profile === MetadataExportProfile::PrivacySafe) {
            $arguments = [
                '-GPS:all=',
                '-XMP-exif:GPSLatitude=',
                '-XMP-exif:GPSLongitude=',
                '-SerialNumber=',
                '-InternalSerialNumber=',
            ];
        } else {
            $arguments = [];
        }

        $fields = [
            'title' => ['-XMP-dc:Title=', $media->title],
            'description' => ['-XMP-dc:Description=', $media->description],
            'creator' => ['-XMP-dc:Creator=', $media->creator],
            'copyright' => ['-XMP-dc:Rights=', $media->copyright],
            'location_name' => ['-XMP-iptcCore:Location=', $media->locationName],
        ];

        $allowed = $policy->profile === MetadataExportProfile::Custom
            ? array_flip($policy->includedFields)
            : null;

        foreach ($fields as $name => [$prefix, $value]) {
            if ($value === null || ($allowed !== null && !isset($allowed[$name]))) {
                continue;
            }
            $arguments[] = $prefix.$value;
        }

        if ($policy->profile !== MetadataExportProfile::PrivacySafe && $policy->profile !== MetadataExportProfile::Custom) {
            if ($media->latitude !== null) {
                $arguments[] = '-XMP-exif:GPSLatitude='.$media->latitude;
            }
            if ($media->longitude !== null) {
                $arguments[] = '-XMP-exif:GPSLongitude='.$media->longitude;
            }
        }

        return $arguments;
    }

    private function extensionFor(string $mimeType): string
    {
        return match (strtolower($mimeType)) {
            'image/jpeg' => 'jpg',
            'image/tiff' => 'tif',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/heic', 'image/heif' => 'heic',
            default => throw new \DomainException('Unsupported metadata export MIME type.'),
        };
    }
}
