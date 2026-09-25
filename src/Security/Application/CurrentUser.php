<?php

declare(strict_types=1);

namespace Mediarama\Security\Application;

interface CurrentUser
{
    public function requireUser(): AuthenticatedUser;
}
