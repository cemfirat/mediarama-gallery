<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add resumable import mappings and checkpoints';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE import_mappings (
    source VARCHAR(64) NOT NULL,
    entity_type VARCHAR(64) NOT NULL,
    source_id VARCHAR(255) NOT NULL,
    target_id UUID NOT NULL,
    imported_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (source, entity_type, source_id)
)
SQL);
        $this->addSql('CREATE INDEX idx_import_mappings_target ON import_mappings (target_id)');

        $this->addSql(<<<'SQL'
CREATE TABLE import_checkpoints (
    source VARCHAR(64) NOT NULL,
    stage VARCHAR(64) NOT NULL,
    cursor VARCHAR(255) NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (source, stage)
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE import_checkpoints');
        $this->addSql('DROP TABLE import_mappings');
    }
}
