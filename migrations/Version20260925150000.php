<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add PostgreSQL full-text search document for media metadata';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
ALTER TABLE media_assets
ADD COLUMN search_document tsvector GENERATED ALWAYS AS (
    setweight(to_tsvector('simple', coalesce(title, '')), 'A') ||
    setweight(to_tsvector('simple', coalesce(description, '')), 'B') ||
    setweight(to_tsvector('simple', coalesce(creator, '')), 'B') ||
    setweight(to_tsvector('simple', coalesce(copyright, '')), 'C') ||
    setweight(to_tsvector('simple', coalesce(camera_make, '') || ' ' || coalesce(camera_model, '') || ' ' || coalesce(lens, '')), 'C') ||
    setweight(to_tsvector('simple', coalesce(location_name, '')), 'C') ||
    setweight(to_tsvector('simple', coalesce(original_filename, '')), 'D')
) STORED
SQL);
        $this->addSql('CREATE INDEX idx_media_search_document ON media_assets USING GIN (search_document)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_media_search_document');
        $this->addSql('ALTER TABLE media_assets DROP COLUMN search_document');
    }
}
