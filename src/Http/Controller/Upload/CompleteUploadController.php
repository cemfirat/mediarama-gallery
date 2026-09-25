<?php

declare(strict_types=1);

namespace Mediarama\Http\Controller\Upload;

use Mediarama\Security\Application\CurrentUser;
use Mediarama\Upload\Application\CompleteChunkedUpload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class CompleteUploadController
{
    public function __construct(private CompleteChunkedUpload $complete, private CurrentUser $currentUser)
    {
    }

    #[Route('/api/uploads/{id}/complete', name: 'upload_complete', methods: ['POST'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        ($this->complete)(
            Uuid::fromString($id),
            $this->currentUser->requireUser()->id,
        );

        return new JsonResponse(['status' => 'uploaded']);
    }
}
