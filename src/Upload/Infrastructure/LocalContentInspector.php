<?php

declare(strict_types=1);

namespace Mediarama\Upload\Infrastructure;

use Mediarama\Media\Application\MediaStorage;
use Mediarama\Media\Domain\MediaType;
use Mediarama\Media\Domain\StorageObjectId;
use Mediarama\Upload\Application\ContentInspector;
use Mediarama\Upload\Application\InspectedContent;

final readonly class LocalContentInspector implements ContentInspector
{
    public function __construct(private MediaStorage $storage)
    {
    }

    public function inspect(StorageObjectId $object): InspectedContent
    {
        $stream = $this->storage->read($object);
        $context = hash_init('sha256');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $prefix = '';

        while (!feof($stream)) {
            $chunk = fread($stream, 1024 * 1024);
            if ($chunk === false) {
                throw new \RuntimeException('Unable to inspect uploaded content.');
            }
            if (strlen($prefix) < 262144) {
                $prefix .= substr($chunk, 0, 262144 - strlen($prefix));
            }
            hash_update($context, $chunk);
        }

        $stat = $this->storage->stat($object);
        $mime = $finfo->buffer($prefix) ?: 'application/octet-stream';
        $type = match (true) {
            str_starts_with($mime, 'image/') => MediaType::Image,
            str_starts_with($mime, 'video/') => MediaType::Video,
            str_starts_with($mime, 'audio/') => MediaType::Audio,
            default => MediaType::Document,
        };

        return new InspectedContent($mime, $type, hash_final($context), $stat->byteSize);
    }
}
