<?php

namespace App\Hiring\Controller\Admin;

use App\Hiring\Repository\JobPostingRepository;
use App\Hiring\Service\HiringService;
use App\Shared\Enum\PositionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/job-postings', name: 'api_admin_job_postings_')]
#[IsGranted('ROLE_ADMIN')]
class JobPostingController extends AbstractController
{
    public function __construct(
        private readonly HiringService $hiringService,
        private readonly JobPostingRepository $jobPostingRepository
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $postings = $this->jobPostingRepository->findBy([], ['openedAt' => 'DESC']);

        $data = array_map(function ($p) {
            return [
                'id' => $p->getId(),
                'positionType' => $p->getPositionType()->value,
                'status' => $p->getStatus()->value,
                'openedAt' => $p->getOpenedAt()->format(\DateTimeInterface::ATOM),
                'applicationCount' => $p->getApplications()->count(),
            ];
        }, $postings);

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $positionTypeRaw = $data['positionType'] ?? null;

        $positionType = PositionType::tryFrom(strtoupper((string)$positionTypeRaw));
        if ($positionType === null) {
            return $this->json([
                'error' => 'Invalid positionType. Allowed: COACH, RECEPTIONIST'
            ], Response::HTTP_BAD_REQUEST);
        }

        $posting = $this->hiringService->createJobPosting($positionType);

        return $this->json([
            'message' => 'Job posting created successfully.',
            'jobPosting' => [
                'id' => $posting->getId(),
                'positionType' => $posting->getPositionType()->value,
                'status' => $posting->getStatus()->value,
                'openedAt' => $posting->getOpenedAt()->format(\DateTimeInterface::ATOM),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}/close', name: 'close', methods: ['PATCH', 'POST'])]
    public function close(int $id): JsonResponse
    {
        $posting = $this->jobPostingRepository->find($id);
        if (!$posting) {
            return $this->json(['error' => 'Job posting not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->hiringService->closeJobPosting($posting);

        return $this->json([
            'message' => 'Job posting closed successfully.',
            'status' => $posting->getStatus()->value,
        ]);
    }
}
