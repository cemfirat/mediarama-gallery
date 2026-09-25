<?php

declare(strict_types=1);

namespace Mediarama\Collection\Domain;

enum Visibility: string
{
    case Public = 'public';
    case Authenticated = 'authenticated';
    case Private = 'private';
    case Restricted = 'restricted';
}
