<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

interface MediaSearch
{
    /** @return list<MediaSearchResult> */
    public function search(MediaSearchCriteria $criteria): array;
}
