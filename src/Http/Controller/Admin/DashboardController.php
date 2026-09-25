<?php

declare(strict_types=1);

namespace Mediarama\Http\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('@Mediarama/admin/dashboard.html.twig');
    }
}
