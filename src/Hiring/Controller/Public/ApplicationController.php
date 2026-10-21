<?php

namespace App\Hiring\Controller\Public;

use App\Hiring\Repository\JobPostingRepository;
use App\Hiring\Service\HiringService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/public/jobs', name: 'api_public_jobs_')]
class ApplicationController extends AbstractController
{
    public function __construct(
        private readonly HiringService $hiringService,
        private readonly JobPostingRepository $jobPostingRepository
    ) {
    }

    #[Route('', name: 'list_open', methods: ['GET'])]
    public function listOpen(): JsonResponse
    {
        $postings = $this->jobPostingRepository->findOpenPostings();

        $data = array_map(function ($p) {
            return [
                'id' => $p->getId(),
                'positionType' => $p->getPositionType()->value,
                'openedAt' => $p->getOpenedAt()->format(\DateTimeInterface::ATOM),
            ];
        }, $postings);

        return $this->json($data);
    }

    #[Route('/{id}/apply', name: 'apply', methods: ['POST'])]
    public function apply(int $id, Request $request): JsonResponse
    {
        $posting = $this->jobPostingRepository->find($id);
        if (!$posting) {
            return $this->json(['error' => 'Job posting not found.'], Response::HTTP_NOT_FOUND);
        }

        $fullName = $request->request->get('fullName') ?? $request->toArray()['fullName'] ?? null;
        $email = $request->request->get('email') ?? $request->toArray()['email'] ?? null;
        $cvPath = $request->request->get('cvPath') ?? $request->toArray()['cvPath'] ?? null;

        // Handle uploaded file if present
        /** @var UploadedFile|null $cvFile */
        $cvFile = $request->files->get('cv');
        if ($cvFile instanceof UploadedFile) {
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cvs';
            $filename = uniqid('cv_', true) . '.' . $cvFile->guessExtension();
            $cvFile->move($uploadsDir, $filename);
            $cvPath = '/uploads/cvs/' . $filename;
        }

        if (empty($fullName) || empty($email) || empty($cvPath)) {
            return $this->json([
                'error' => 'fullName, email, and CV (file upload or cvPath link) are required.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email address.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $application = $this->hiringService->submitApplication($posting, $fullName, $email, $cvPath);

            return $this->json([
                'message' => 'Application submitted successfully. A confirmation email has been sent.',
                'application' => [
                    'id' => $application->getId(),
                    'fullName' => $application->getFullName(),
                    'email' => $application->getEmail(),
                    'position' => $posting->getPositionType()->value,
                    'status' => $application->getStatus()->value,
                    'submittedAt' => $application->getSubmittedAt()->format(\DateTimeInterface::ATOM),
                ]
            ], Response::HTTP_CREATED);
        } catch (LogicException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
