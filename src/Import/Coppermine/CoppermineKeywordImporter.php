<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;
use Mediarama\Import\Application\ImportMappingRepository;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;

final readonly class CoppermineKeywordImporter
{
    public function __construct(
        private CoppermineConnectionFactory $sourceFactory,
        private Connection $target,
        private ImportMappingRepository $mappings,
        private CoppermineTablePrefix $prefix,
    ) {
    }

    public function import(): CoppermineKeywordImportReport
    {
        $source = $this->sourceFactory->create();
        $pictures = $source->quoteIdentifier($this->prefix->table('pictures'));
        $config = $source->quoteIdentifier($this->prefix->table('config'));

        $separator = (string) ($source->fetchOne(
            'SELECT value FROM '.$config." WHERE name = 'keyword_separator'",
        ) ?: ';');

        if ($separator === '') {
            $separator = ';';
        }

        $rows = $source->fetchAllAssociative(
            'SELECT pid, keywords FROM '.$pictures." WHERE keywords <> '' ORDER BY pid ASC",
        );

        $slugger = new AsciiSlugger();
        $tags = 0;
        $links = 0;
        $unmapped = [];

        foreach ($rows as $row) {
            $mediaId = $this->mappings->findTargetId('coppermine', 'picture', (string) $row['pid']);
            if ($mediaId === null) {
                $unmapped[] = (string) $row['pid'];
                continue;
            }

            $keywords = array_unique(array_filter(array_map(
                static fn (string $keyword): string => trim(html_entity_decode($keyword, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                explode($separator, (string) $row['keywords']),
            )));

            foreach ($keywords as $keyword) {
                $slug = strtolower($slugger->slug($keyword)->toString());
                if ($slug === '') {
                    continue;
                }

                $tagId = $this->target->fetchOne('SELECT id FROM tags WHERE slug = :slug', ['slug' => $slug]);
                if ($tagId === false) {
                    $tagId = Uuid::v7()->toRfc4122();
                    $this->target->insert('tags', [
                        'id' => $tagId,
                        'slug' => $slug,
                        'name' => $keyword,
                        'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                        'updated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                    ]);
                    ++$tags;
                }

                $affected = $this->target->executeStatement(
                    <<<'SQL'
INSERT INTO media_tags (media_id, tag_id, source)
VALUES (:media_id, :tag_id, 'coppermine')
ON CONFLICT (media_id, tag_id) DO NOTHING
SQL,
                    ['media_id' => $mediaId->toRfc4122(), 'tag_id' => (string) $tagId],
                );
                $links += $affected;
            }
        }

        return new CoppermineKeywordImportReport($tags, $links, $unmapped, $separator);
    }
}
