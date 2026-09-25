<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Mediarama foundation schema for media, collections, identity, interactions and imports';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS citext');

        $this->addSql(<<<'SQL'
CREATE TABLE users (
    id UUID NOT NULL,
    username VARCHAR(120) NOT NULL,
    email CITEXT DEFAULT NULL,
    password_hash TEXT DEFAULT NULL,
    display_name TEXT DEFAULT NULL,
    status VARCHAR(32) NOT NULL,
    locale VARCHAR(16) DEFAULT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    last_login_at TIMESTAMPTZ DEFAULT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_users_username ON users (username)');
        $this->addSql('CREATE UNIQUE INDEX uniq_users_email ON users (email) WHERE email IS NOT NULL');

        $this->addSql(<<<'SQL'
CREATE TABLE groups (
    id UUID NOT NULL,
    slug VARCHAR(120) NOT NULL,
    name TEXT NOT NULL,
    is_system BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_groups_slug ON groups (slug)');

        $this->addSql(<<<'SQL'
CREATE TABLE permissions (
    permission_key VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    PRIMARY KEY(permission_key)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE user_groups (
    user_id UUID NOT NULL,
    group_id UUID NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(user_id, group_id),
    CONSTRAINT fk_user_groups_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_groups_group FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_user_primary_group ON user_groups (user_id) WHERE is_primary = TRUE');

        $this->addSql(<<<'SQL'
CREATE TABLE group_permissions (
    group_id UUID NOT NULL,
    permission_key VARCHAR(160) NOT NULL,
    PRIMARY KEY(group_id, permission_key),
    CONSTRAINT fk_group_permissions_group FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE,
    CONSTRAINT fk_group_permissions_permission FOREIGN KEY (permission_key) REFERENCES permissions (permission_key) ON DELETE CASCADE
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE media_assets (
    id UUID NOT NULL,
    owner_id UUID DEFAULT NULL,
    storage_disk VARCHAR(80) NOT NULL,
    storage_key TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    mime_type VARCHAR(255) NOT NULL,
    media_type VARCHAR(32) NOT NULL,
    byte_size BIGINT NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    width INT DEFAULT NULL,
    height INT DEFAULT NULL,
    duration_ms BIGINT DEFAULT NULL,
    title TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    captured_at TIMESTAMPTZ DEFAULT NULL,
    processing_state VARCHAR(32) NOT NULL,
    moderation_state VARCHAR(32) NOT NULL,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    deleted_at TIMESTAMPTZ DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_media_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_media_byte_size CHECK (byte_size >= 0),
    CONSTRAINT chk_media_width CHECK (width IS NULL OR width > 0),
    CONSTRAINT chk_media_height CHECK (height IS NULL OR height > 0),
    CONSTRAINT chk_media_duration CHECK (duration_ms IS NULL OR duration_ms >= 0)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_media_storage ON media_assets (storage_disk, storage_key)');
        $this->addSql('CREATE INDEX idx_media_processing_state ON media_assets (processing_state)');
        $this->addSql('CREATE INDEX idx_media_moderation_state ON media_assets (moderation_state)');
        $this->addSql('CREATE INDEX idx_media_created_at ON media_assets (created_at DESC)');
        $this->addSql('CREATE INDEX idx_media_metadata_gin ON media_assets USING GIN (metadata)');

        $this->addSql(<<<'SQL'
CREATE TABLE media_derivatives (
    id UUID NOT NULL,
    media_id UUID NOT NULL,
    kind VARCHAR(64) NOT NULL,
    profile VARCHAR(120) NOT NULL,
    processing_version INT NOT NULL,
    storage_disk VARCHAR(80) NOT NULL,
    storage_key TEXT NOT NULL,
    mime_type VARCHAR(255) NOT NULL,
    byte_size BIGINT NOT NULL,
    width INT DEFAULT NULL,
    height INT DEFAULT NULL,
    duration_ms BIGINT DEFAULT NULL,
    metadata JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_derivative_media FOREIGN KEY (media_id) REFERENCES media_assets (id) ON DELETE CASCADE,
    CONSTRAINT chk_derivative_byte_size CHECK (byte_size >= 0)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_derivative_profile ON media_derivatives (media_id, kind, profile, processing_version)');
        $this->addSql('CREATE UNIQUE INDEX uniq_derivative_storage ON media_derivatives (storage_disk, storage_key)');

        $this->addSql(<<<'SQL'
CREATE TABLE collections (
    id UUID NOT NULL,
    owner_id UUID DEFAULT NULL,
    parent_id UUID DEFAULT NULL,
    cover_media_id UUID DEFAULT NULL,
    slug VARCHAR(160) DEFAULT NULL,
    title TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    visibility VARCHAR(32) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    deleted_at TIMESTAMPTZ DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_collection_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_collection_parent FOREIGN KEY (parent_id) REFERENCES collections (id) ON DELETE SET NULL,
    CONSTRAINT fk_collection_cover FOREIGN KEY (cover_media_id) REFERENCES media_assets (id) ON DELETE SET NULL
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_collection_slug ON collections (slug) WHERE slug IS NOT NULL');

        $this->addSql(<<<'SQL'
CREATE TABLE collection_media (
    collection_id UUID NOT NULL,
    media_id UUID NOT NULL,
    position INT NOT NULL DEFAULT 0,
    added_by UUID DEFAULT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(collection_id, media_id),
    CONSTRAINT fk_collection_media_collection FOREIGN KEY (collection_id) REFERENCES collections (id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_media_media FOREIGN KEY (media_id) REFERENCES media_assets (id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_media_added_by FOREIGN KEY (added_by) REFERENCES users (id) ON DELETE SET NULL
)
SQL);
        $this->addSql('CREATE INDEX idx_collection_media_position ON collection_media (collection_id, position)');

        $this->addSql(<<<'SQL'
CREATE TABLE collection_access (
    id UUID NOT NULL,
    collection_id UUID NOT NULL,
    user_id UUID DEFAULT NULL,
    group_id UUID DEFAULT NULL,
    capability VARCHAR(160) NOT NULL,
    effect VARCHAR(16) NOT NULL DEFAULT 'allow',
    created_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_collection_access_collection FOREIGN KEY (collection_id) REFERENCES collections (id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_access_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_access_group FOREIGN KEY (group_id) REFERENCES groups (id) ON DELETE CASCADE,
    CONSTRAINT chk_collection_access_principal CHECK (
        (user_id IS NOT NULL AND group_id IS NULL)
        OR (user_id IS NULL AND group_id IS NOT NULL)
    )
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE tags (
    id UUID NOT NULL,
    slug VARCHAR(160) NOT NULL,
    name TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_tags_slug ON tags (slug)');

        $this->addSql(<<<'SQL'
CREATE TABLE media_tags (
    media_id UUID NOT NULL,
    tag_id UUID NOT NULL,
    source VARCHAR(32) NOT NULL DEFAULT 'manual',
    PRIMARY KEY(media_id, tag_id),
    CONSTRAINT fk_media_tags_media FOREIGN KEY (media_id) REFERENCES media_assets (id) ON DELETE CASCADE,
    CONSTRAINT fk_media_tags_tag FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE favorites (
    user_id UUID NOT NULL,
    media_id UUID NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(user_id, media_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_media FOREIGN KEY (media_id) REFERENCES media_assets (id) ON DELETE CASCADE
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE comments (
    id UUID NOT NULL,
    media_id UUID NOT NULL,
    user_id UUID DEFAULT NULL,
    guest_name TEXT DEFAULT NULL,
    body TEXT NOT NULL,
    moderation_state VARCHAR(32) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    deleted_at TIMESTAMPTZ DEFAULT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_comments_media FOREIGN KEY (media_id) REFERENCES media_assets (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE ratings (
    user_id UUID NOT NULL,
    media_id UUID NOT NULL,
    value SMALLINT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(user_id, media_id),
    CONSTRAINT fk_ratings_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_ratings_media FOREIGN KEY (media_id) REFERENCES media_assets (id) ON DELETE CASCADE,
    CONSTRAINT chk_rating_value CHECK (value BETWEEN 1 AND 5)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE upload_sessions (
    id UUID NOT NULL,
    user_id UUID NOT NULL,
    target_collection_id UUID DEFAULT NULL,
    original_filename TEXT NOT NULL,
    expected_size BIGINT NOT NULL,
    expected_mime VARCHAR(255) DEFAULT NULL,
    temporary_storage_key TEXT NOT NULL,
    status VARCHAR(32) NOT NULL,
    expires_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(id),
    CONSTRAINT fk_upload_session_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_upload_session_collection FOREIGN KEY (target_collection_id) REFERENCES collections (id) ON DELETE SET NULL,
    CONSTRAINT chk_upload_expected_size CHECK (expected_size >= 0)
)
SQL);
        $this->addSql('CREATE INDEX idx_upload_sessions_expiry ON upload_sessions (expires_at)');
        $this->addSql('CREATE INDEX idx_upload_sessions_status ON upload_sessions (status)');

        $this->addSql(<<<'SQL'
CREATE TABLE import_runs (
    id UUID NOT NULL,
    source_type VARCHAR(64) NOT NULL,
    source_version VARCHAR(64) DEFAULT NULL,
    status VARCHAR(32) NOT NULL,
    options JSONB NOT NULL DEFAULT '{}'::jsonb,
    progress JSONB NOT NULL DEFAULT '{}'::jsonb,
    started_at TIMESTAMPTZ DEFAULT NULL,
    completed_at TIMESTAMPTZ DEFAULT NULL,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    PRIMARY KEY(id)
)
SQL);

        $this->addSql(<<<'SQL'
CREATE TABLE import_id_map (
    import_run_id UUID NOT NULL,
    entity_type VARCHAR(64) NOT NULL,
    source_id VARCHAR(255) NOT NULL,
    target_id UUID NOT NULL,
    PRIMARY KEY(import_run_id, entity_type, source_id),
    CONSTRAINT fk_import_map_run FOREIGN KEY (import_run_id) REFERENCES import_runs (id) ON DELETE CASCADE
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE import_id_map');
        $this->addSql('DROP TABLE import_runs');
        $this->addSql('DROP TABLE upload_sessions');
        $this->addSql('DROP TABLE ratings');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE favorites');
        $this->addSql('DROP TABLE media_tags');
        $this->addSql('DROP TABLE tags');
        $this->addSql('DROP TABLE collection_access');
        $this->addSql('DROP TABLE collection_media');
        $this->addSql('DROP TABLE collections');
        $this->addSql('DROP TABLE media_derivatives');
        $this->addSql('DROP TABLE media_assets');
        $this->addSql('DROP TABLE group_permissions');
        $this->addSql('DROP TABLE user_groups');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE groups');
        $this->addSql('DROP TABLE users');
    }
}
