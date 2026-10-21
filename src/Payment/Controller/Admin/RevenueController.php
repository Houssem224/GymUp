<?php

namespace App\Payment\Controller\Admin;

use App\Payment\Repository\PaymentRepository;
use App\Payment\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/revenue', name: 'api_admin_revenue_')]
#[IsGranted('ROLE_ADMIN')]
class RevenueController extends AbstractController
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentRepository $paymentRepository
    ) {
    }

    #[Route('/summary', name: 'summary', methods: ['GET'])]
    public function summary(Request $request): JsonResponse
    {
        $startDateParam = $request->query->get('startDate');
        $endDateParam = $request->query->get('endDate');

        $startDate = $startDateParam ? new \DateTimeImmutable($startDateParam) : null;
        $endDate = $endDateParam ? new \DateTimeImmutable($endDateParam) : null;

        $summary = $this->paymentService->getRevenueReport($startDate, $endDate);

        return $this->json($summary);
    }

    #[Route('/payments', name: 'payments', methods: ['GET'])]
    public function payments(Request $request): JsonResponse
    {
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
        $payments = $this->paymentRepository->findRecent($limit);

        $data = array_map(function ($p) {
            return [
                'id' => $p->getId(),
                'member' => [
                    'id' => $p->getMember()->getId(),
                    'email' => $p->getMember()->getEmail(),
                    'fullName' => $p->getMember()->getFullName(),
                ],
                'plan' => $p->getSubscription()->getPlan()?->getName(),
                'amount' => $p->getAmount(),
                'method' => $p->getMethod()->value,
                'processedBy' => $p->getProcessedBy()?->getEmail(),
                'paidAt' => $p->getPaidAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $payments);

        return $this->json($data);
    }
}
