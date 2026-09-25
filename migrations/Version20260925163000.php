<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Preserve legacy rating aggregates during Coppermine migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE import_rating_aggregates (
    source VARCHAR(64) NOT NULL,
    media_id UUID NOT NULL,
    average_5 NUMERIC(4,2) NOT NULL,
    vote_count INT NOT NULL,
    imported_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (source, media_id),
    CONSTRAINT fk_import_rating_media FOREIGN KEY (media_id) REFERENCES media_assets (id) ON DELETE CASCADE,
    CONSTRAINT chk_import_rating_average CHECK (average_5 BETWEEN 0 AND 5),
    CONSTRAINT chk_import_rating_count CHECK (vote_count >= 0)
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE import_rating_aggregates');
    }
}
