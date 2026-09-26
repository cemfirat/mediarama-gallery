<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926192500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reserve stable upload finalization media IDs and track processing dispatch';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upload_sessions ADD finalization_media_id UUID DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_upload_session_finalization_media ON upload_sessions (finalization_media_id) WHERE finalization_media_id IS NOT NULL');
        $this->addSql("COMMENT ON COLUMN upload_sessions.finalization_media_id IS 'Reserved before storage promotion so retries converge on one MediaAsset identity'");
        $this->addSql('ALTER TABLE upload_finalizations ADD processing_dispatched_at TIMESTAMPTZ DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upload_finalizations DROP processing_dispatched_at');
        $this->addSql('DROP INDEX uniq_upload_session_finalization_media');
        $this->addSql('ALTER TABLE upload_sessions DROP finalization_media_id');
    }
}
