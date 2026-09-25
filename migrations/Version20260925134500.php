<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925134500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add normalized searchable media metadata and provenance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE media_assets ADD metadata_provenance JSONB NOT NULL DEFAULT '{}'::jsonb");
        $this->addSql('ALTER TABLE media_assets ADD creator TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD copyright TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD camera_make TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD camera_model TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD lens TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD iso INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD aperture VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD exposure_time VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD focal_length VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD latitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE media_assets ADD location_name TEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE media_assets ADD CONSTRAINT chk_media_iso CHECK (iso IS NULL OR iso > 0)');
        $this->addSql('ALTER TABLE media_assets ADD CONSTRAINT chk_media_latitude CHECK (latitude IS NULL OR latitude BETWEEN -90 AND 90)');
        $this->addSql('ALTER TABLE media_assets ADD CONSTRAINT chk_media_longitude CHECK (longitude IS NULL OR longitude BETWEEN -180 AND 180)');

        $this->addSql('CREATE INDEX idx_media_captured_at ON media_assets (captured_at)');
        $this->addSql('CREATE INDEX idx_media_camera ON media_assets (camera_make, camera_model)');
        $this->addSql('CREATE INDEX idx_media_creator ON media_assets (creator)');
        $this->addSql('CREATE INDEX idx_media_metadata_provenance_gin ON media_assets USING GIN (metadata_provenance)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_media_metadata_provenance_gin');
        $this->addSql('DROP INDEX idx_media_creator');
        $this->addSql('DROP INDEX idx_media_camera');
        $this->addSql('DROP INDEX idx_media_captured_at');

        $this->addSql('ALTER TABLE media_assets DROP metadata_provenance');
        $this->addSql('ALTER TABLE media_assets DROP creator');
        $this->addSql('ALTER TABLE media_assets DROP copyright');
        $this->addSql('ALTER TABLE media_assets DROP camera_make');
        $this->addSql('ALTER TABLE media_assets DROP camera_model');
        $this->addSql('ALTER TABLE media_assets DROP lens');
        $this->addSql('ALTER TABLE media_assets DROP iso');
        $this->addSql('ALTER TABLE media_assets DROP aperture');
        $this->addSql('ALTER TABLE media_assets DROP exposure_time');
        $this->addSql('ALTER TABLE media_assets DROP focal_length');
        $this->addSql('ALTER TABLE media_assets DROP latitude');
        $this->addSql('ALTER TABLE media_assets DROP longitude');
        $this->addSql('ALTER TABLE media_assets DROP location_name');
    }
}
