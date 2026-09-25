<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Mediarama\Media\Domain\MediaType;

final readonly class UploadContentPolicy
{
    /** @param list<string> $allowedMimeTypes */
    public function __construct(private array $allowedMimeTypes)
    {
    }

    public function assertAllowed(InspectedContent $content): void
    {
        if (!in_array($content->mimeType, $this->allowedMimeTypes, true)) {
            throw new \DomainException(sprintf(
                'MIME type "%s" is not allowed for upload.',
                $content->mimeType,
            ));
        }

        if ($content->mediaType === MediaType::Document) {
            throw new \DomainException('Generic document uploads are not enabled.');
        }
    }
}
