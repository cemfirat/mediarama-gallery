<?php

declare(strict_types=1);

namespace Mediarama\Security\Application;

use Symfony\Component\Uid\Uuid;

final readonly class AuthenticatedUser
{
    public function __construct(public Uuid $id)
    {
    }
}
