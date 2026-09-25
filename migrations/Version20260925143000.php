<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track upload finalization for idempotent finalize requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE upload_finalizations (
    upload_session_id UUID PRIMARY KEY REFERENCES upload_sessions(id) ON DELETE CASCADE,
    media_id UUID NOT NULL UNIQUE REFERENCES media_assets(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE upload_finalizations');
    }
}
