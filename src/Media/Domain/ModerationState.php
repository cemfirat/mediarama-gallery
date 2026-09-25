<?php

declare(strict_types=1);

namespace Mediarama\Media\Domain;

enum ModerationState: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
}
