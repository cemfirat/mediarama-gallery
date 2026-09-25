<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

final readonly class CoppermineReconciliationReport
{
    /** @param list<array{pid:string,path:string}> $missingFiles
     *  @param list<string> $unmappedPictureIds
     */
    public function __construct(
        public int $sourcePictures,
        public int $mappedPictures,
        public int $targetMedia,
        public int $collectionLinks,
        public array $missingFiles,
        public array $unmappedPictureIds,
    ) {
    }

    public function isClean(): bool
    {
        return $this->sourcePictures === $this->mappedPictures
            && $this->mappedPictures === $this->targetMedia
            && $this->missingFiles === []
            && $this->unmappedPictureIds === [];
    }
}
