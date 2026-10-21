<?php

namespace App\Admin\Controller;

use App\Admin\Service\DashboardAggregationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/dashboard', name: 'api_admin_dashboard_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardAggregationService $aggregationService
    ) {
    }

    #[Route('', name: 'overview', methods: ['GET'])]
    public function overview(): JsonResponse
    {
        $metrics = $this->aggregationService->getDashboardMetrics();

        return $this->json($metrics);
    }
}
