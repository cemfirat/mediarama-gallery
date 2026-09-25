<?php

declare(strict_types=1);

namespace Mediarama\Security\Infrastructure\Http;

use Mediarama\Security\Application\AuthenticatedUser;
use Mediarama\Security\Application\CurrentUser;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final readonly class RequestCurrentUser implements CurrentUser
{
    public function __construct(private RequestStack $requests)
    {
    }

    public function requireUser(): AuthenticatedUser
    {
        $request = $this->requests->getCurrentRequest();
        $value = $request?->attributes->get('_mediarama_user_id');

        if (!is_string($value) || $value === '') {
            throw new \DomainException('Authentication is required.');
        }

        return new AuthenticatedUser(Uuid::fromString($value));
    }
}
