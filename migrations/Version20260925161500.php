<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925161500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add collection view ACL support and safe password-protection migration fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "INSERT INTO permissions (permission_key, description)
             VALUES ('collection.view', 'View a restricted collection.')
             ON CONFLICT (permission_key) DO NOTHING"
        );

        $this->addSql('ALTER TABLE collections ADD COLUMN password_protected BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE collections ADD COLUMN password_hash TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE collections ADD COLUMN password_hint TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE collections ADD COLUMN password_reset_required BOOLEAN NOT NULL DEFAULT FALSE');

        $this->addSql(
            'CREATE UNIQUE INDEX uniq_collection_access_user_capability
             ON collection_access (collection_id, user_id, capability, effect)
             WHERE user_id IS NOT NULL'
        );
        $this->addSql(
            'CREATE UNIQUE INDEX uniq_collection_access_group_capability
             ON collection_access (collection_id, group_id, capability, effect)
             WHERE group_id IS NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_collection_access_group_capability');
        $this->addSql('DROP INDEX uniq_collection_access_user_capability');
        $this->addSql('ALTER TABLE collections DROP COLUMN password_reset_required');
        $this->addSql('ALTER TABLE collections DROP COLUMN password_hint');
        $this->addSql('ALTER TABLE collections DROP COLUMN password_hash');
        $this->addSql('ALTER TABLE collections DROP COLUMN password_protected');
        $this->addSql("DELETE FROM permissions WHERE permission_key = 'collection.view'");
    }
}
