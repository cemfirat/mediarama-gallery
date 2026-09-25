<?php

declare(strict_types=1);

namespace Mediarama\Http\Controller\Upload;

use Mediarama\Upload\Application\FinalizeUpload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class FinalizeUploadController
{
    public function __construct(private FinalizeUpload $finalize)
    {
    }

    #[Route('/api/uploads/{id}/finalize', name: 'upload_finalize', methods: ['POST'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $media = ($this->finalize)(
            Uuid::fromString($id),
            Uuid::fromString((string) $request->headers->get('X-Mediarama-User')),
        );

        return new JsonResponse([
            'media_id' => $media->id->toRfc4122(),
            'processing_state' => $media->processingState->value,
        ], 202);
    }
}
