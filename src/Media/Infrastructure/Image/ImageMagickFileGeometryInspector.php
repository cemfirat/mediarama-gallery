<?php

declare(strict_types=1);

namespace Mediarama\Media\Infrastructure\Image;

use Mediarama\Media\Application\InspectImageFileGeometry;

final readonly class ImageMagickFileGeometryInspector implements InspectImageFileGeometry
{
    public function __construct(private ImageMagickProcess $process)
    {
    }

    public function __invoke(string $path): array
    {
        try {
            $output = $this->process->identify([
                '-format',
                '%w %h %[orientation]',
                $path.'[0]',
            ]);
        } catch (\RuntimeException $error) {
            throw new \RuntimeException(
                'Unable to inspect image geometry: '.$error->getMessage(),
                0,
                $error,
            );
        }

        $parts = preg_split('/\s+/', trim($output));
        if ($parts === false || count($parts) < 2) {
            throw new \RuntimeException('Image geometry response is invalid.');
        }

        $width = (int) $parts[0];
        $height = (int) $parts[1];
        $orientation = strtolower((string) ($parts[2] ?? ''));

        if (in_array($orientation, ['lefttop', 'righttop', 'rightbottom', 'leftbottom'], true)) {
            [$width, $height] = [$height, $width];
        }

        if ($width < 1 || $height < 1) {
            throw new \RuntimeException('Image dimensions are invalid.');
        }

        return ['width' => $width, 'height' => $height];
    }
}
