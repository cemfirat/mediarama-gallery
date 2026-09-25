<?php

declare(strict_types=1);

namespace Mediarama\Collection\Domain;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class Collection
{
    private function __construct(
        public readonly Uuid $id,
        public readonly ?Uuid $ownerId,
        public string $title,
        public ?string $description,
        public Visibility $visibility,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?Uuid $parentId = null,
        public ?Uuid $coverMediaId = null,
        public ?string $slug = null,
        public int $position = 0,
    ) {
        if (trim($title) === '') {
            throw new \InvalidArgumentException('Collection title must not be empty.');
        }
    }

    public static function create(?Uuid $ownerId, string $title, Visibility $visibility = Visibility::Public): self
    {
        $now = new DateTimeImmutable();

        return new self(Uuid::v7(), $ownerId, trim($title), null, $visibility, $now, $now);
    }
}
