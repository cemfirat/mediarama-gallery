<?php

declare(strict_types=1);

namespace Mediarama\Upload\Application;

use Symfony\Component\Uid\Uuid;

/**
 * Foundation fallback for installations before collection ACL persistence is
 * wired. It only permits uploads without an explicit destination.
 *
 * This is intentionally restrictive: collection uploads cannot accidentally
 * bypass authorization while the ACL implementation is incomplete.
 */
final class AllowAllUploadDestinationAuthorizer implements UploadDestinationAuthorizer
{
    public function assertCanUpload(Uuid $userId, ?Uuid $collectionId): void
    {
        if ($collectionId !== null) {
            throw new \DomainException('Collection upload authorization is not configured yet.');
        }
    }
}
