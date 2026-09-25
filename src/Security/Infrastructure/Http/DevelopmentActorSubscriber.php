<?php

declare(strict_types=1);

namespace Mediarama\Security\Infrastructure\Http;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', priority: 100)]
final readonly class DevelopmentActorSubscriber
{
    public function __construct(private string $appEnvironment)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if ($this->appEnvironment !== 'dev' && $this->appEnvironment !== 'test') {
            return;
        }

        $request = $event->getRequest();
        $actor = $request->headers->get('X-Mediarama-User');

        if (is_string($actor) && $actor !== '') {
            $request->attributes->set('_mediarama_user_id', $actor);
        }
    }
}
