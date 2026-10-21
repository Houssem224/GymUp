<?php

namespace App\Hiring\Controller\Admin;

use App\Hiring\Repository\ApplicationRepository;
use App\Hiring\Service\HiringService;
use App\Shared\Enum\ApplicationStatus;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/applications', name: 'api_admin_applications_')]
#[IsGranted('ROLE_ADMIN')]
class ApplicationReviewController extends AbstractController
{
    public function __construct(
        private readonly HiringService $hiringService,
        private readonly ApplicationRepository $applicationRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $statusParam = $request->query->get('status');
        $status = $statusParam ? ApplicationStatus::tryFrom(strtoupper($statusParam)) : null;

        $applications = $status 
            ? $this->applicationRepository->findBy(['status' => $status], ['submittedAt' => 'DESC'])
            : $this->applicationRepository->findBy([], ['submittedAt' => 'DESC']);

        $data = array_map(function ($app) {
            return [
                'id' => $app->getId(),
                'jobPostingId' => $app->getJobPosting()->getId(),
                'positionType' => $app->getJobPosting()->getPositionType()->value,
                'fullName' => $app->getFullName(),
                'email' => $app->getEmail(),
                'cvPath' => $app->getCvPath(),
                'status' => $app->getStatus()->value,
                'submittedAt' => $app->getSubmittedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $applications);

        return $this->json($data);
    }

    #[Route('/{id}/accept', name: 'accept', methods: ['POST'])]
    public function accept(int $id): JsonResponse
    {
        $application = $this->applicationRepository->find($id);
        if (!$application) {
            return $this->json(['error' => 'Application not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $user = $this->hiringService->acceptApplication($application);

            return $this->json([
                'message' => 'Application accepted successfully. Staff account created and credentials dispatched.',
                'applicationStatus' => $application->getStatus()->value,
                'staffAccount' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'fullName' => $user->getFullName(),
                    'role' => $user->getRole()->value,
                ]
            ]);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(int $id): JsonResponse
    {
        $application = $this->applicationRepository->find($id);
        if (!$application) {
            return $this->json(['error' => 'Application not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->hiringService->rejectApplication($application);

            return $this->json([
                'message' => 'Application rejected. Candidate informed via email.',
                'applicationStatus' => $application->getStatus()->value,
            ]);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
