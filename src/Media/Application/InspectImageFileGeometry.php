<?php

declare(strict_types=1);

namespace Mediarama\Media\Application;

interface InspectImageFileGeometry
{
    /** @return array{width:int,height:int} */
    public function __invoke(string $path): array;
}
