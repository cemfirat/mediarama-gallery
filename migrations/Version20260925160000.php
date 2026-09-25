<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed core permission keys used by imported groups';
    }

    public function up(Schema $schema): void
    {
        $permissions = [
            'system.admin' => 'Administrative access to Mediarama.',
            'media.upload' => 'Upload media.',
            'media.rate' => 'Rate media.',
            'media.comment' => 'Post comments.',
            'collection.create' => 'Create collections.',
            'collection.media.add' => 'Add media to allowed collections.',
        ];

        foreach ($permissions as $key => $description) {
            $this->addSql(
                'INSERT INTO permissions (permission_key, description) VALUES (:key, :description) ON CONFLICT (permission_key) DO NOTHING',
                ['key' => $key, 'description' => $description],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM permissions WHERE permission_key IN (
            'system.admin','media.upload','media.rate','media.comment','collection.create','collection.media.add'
        )");
    }
}
