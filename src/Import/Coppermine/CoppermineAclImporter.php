<?php

declare(strict_types=1);

namespace Mediarama\Import\Coppermine;

use Doctrine\DBAL\Connection;
use Mediarama\Import\Application\ImportMappingRepository;
use Symfony\Component\Uid\Uuid;

final readonly class CoppermineAclImporter
{
    private const FIRST_USER_CAT = 10000;

    public function __construct(
        private CoppermineConnectionFactory $sourceFactory,
        private Connection $target,
        private ImportMappingRepository $mappings,
        private CoppermineTablePrefix $prefix,
    ) {
    }

    public function import(): CoppermineAclImportReport
    {
        $source = $this->sourceFactory->create();
        $table = $source->quoteIdentifier($this->prefix->table('albums'));

        $rows = $source->fetchAllAssociative(
            'SELECT aid, owner, visibility, alb_password, alb_password_hint FROM '.$table.' ORDER BY aid ASC',
        );

        $rules = 0;
        $unmapped = [];
        $passwordReset = [];

        foreach ($rows as $row) {
            $aid = (string) $row['aid'];
            $collectionId = $this->mappings->findTargetId('coppermine', 'album', $aid);
            if ($collectionId === null) {
                $unmapped[] = 'album:'.$aid;
                continue;
            }

            $passwordProtected = trim((string) $row['alb_password']) !== '';
            if ($passwordProtected) {
                // Coppermine stores album passwords as MD5. Never reuse that hash.
                $this->target->update('collections', [
                    'visibility' => 'restricted',
                    'password_protected' => true,
                    'password_hash' => null,
                    'password_hint' => trim((string) $row['alb_password_hint']) !== ''
                        ? (string) $row['alb_password_hint']
                        : null,
                    'password_reset_required' => true,
                    'updated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                ], ['id' => $collectionId->toRfc4122()]);
                $passwordReset[] = $aid;
            }

            $ownerId = $this->mappings->findTargetId('coppermine', 'user', (string) $row['owner']);
            if ($ownerId !== null) {
                $rules += $this->rememberRule($collectionId, $ownerId, null);
            }

            $visibility = (int) $row['visibility'];
            if ($visibility === 0) {
                continue;
            }

            $this->target->update(
                'collections',
                [
                    'visibility' => 'restricted',
                    'updated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                ],
                ['id' => $collectionId->toRfc4122()],
            );

            if ($visibility >= self::FIRST_USER_CAT) {
                $sourceUserId = (string) ($visibility - self::FIRST_USER_CAT);
                $userId = $this->mappings->findTargetId('coppermine', 'user', $sourceUserId);

                if ($userId === null) {
                    $unmapped[] = sprintf('album:%s user:%s', $aid, $sourceUserId);
                    continue;
                }

                $rules += $this->rememberRule($collectionId, $userId, null);
                continue;
            }

            $groupId = $this->mappings->findTargetId('coppermine', 'group', (string) $visibility);
            if ($groupId === null) {
                $unmapped[] = sprintf('album:%s group:%d', $aid, $visibility);
                continue;
            }

            $rules += $this->rememberRule($collectionId, null, $groupId);
        }

        return new CoppermineAclImportReport(count($rows), $rules, $unmapped, $passwordReset);
    }

    private function rememberRule(Uuid $collectionId, ?Uuid $userId, ?Uuid $groupId): int
    {
        return $this->target->executeStatement(
            <<<'SQL'
INSERT INTO collection_access (
    id, collection_id, user_id, group_id, capability, effect, created_at
) VALUES (
    :id, :collection_id, :user_id, :group_id, 'collection.view', 'allow', NOW()
)
ON CONFLICT DO NOTHING
SQL,
            [
                'id' => Uuid::v7()->toRfc4122(),
                'collection_id' => $collectionId->toRfc4122(),
                'user_id' => $userId?->toRfc4122(),
                'group_id' => $groupId?->toRfc4122(),
            ],
        );
    }
}
