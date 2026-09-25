<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

use Mediarama\Media\Domain\MediaAsset;

final readonly class GenerateImageDerivatives
{
    /** @param list<ImageDerivativeProfile> $profiles */
    public function __construct(
        private MediaDerivativeRepository $derivatives,
        private ImageDerivativeGenerator $generator,
        private array $profiles,
        private int $processingVersion,
    ) {
    }

    public function __invoke(MediaAsset $media): void
    {
        foreach ($this->profiles as $profile) {
            $existing = $this->derivatives->find(
                $media->id,
                'image',
                $profile->name,
                $this->processingVersion,
            );

            if ($existing !== null) {
                continue;
            }

            $this->derivatives->save(
                $this->generator->generate($media, $profile, $this->processingVersion),
            );
        }
    }
}
