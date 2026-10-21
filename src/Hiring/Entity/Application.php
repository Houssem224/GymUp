<?php

namespace App\Hiring\Entity;

use App\Hiring\Repository\ApplicationRepository;
use App\Shared\Enum\ApplicationStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'applications')]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: JobPosting::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(name: 'job_posting_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?JobPosting $jobPosting = null;

    #[ORM\Column(name: 'full_name', type: 'string', length: 150)]
    private string $fullName;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(name: 'cv_path', type: 'string', length: 255)]
    private string $cvPath;

    #[ORM\Column(type: 'string', enumType: ApplicationStatus::class)]
    private ApplicationStatus $status = ApplicationStatus::PENDING;

    #[ORM\Column(name: 'submitted_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $submittedAt;

    public function __construct(JobPosting $jobPosting, string $fullName, string $email, string $cvPath)
    {
        $this->jobPosting = $jobPosting;
        $this->fullName = $fullName;
        $this->email = $email;
        $this->cvPath = $cvPath;
        $this->status = ApplicationStatus::PENDING;
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobPosting(): ?JobPosting
    {
        return $this->jobPosting;
    }

    public function setJobPosting(?JobPosting $jobPosting): self
    {
        $this->jobPosting = $jobPosting;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getCvPath(): string
    {
        return $this->cvPath;
    }

    public function setCvPath(string $cvPath): self
    {
        $this->cvPath = $cvPath;
        return $this;
    }

    public function getStatus(): ApplicationStatus
    {
        return $this->status;
    }

    public function setStatus(ApplicationStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === ApplicationStatus::PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === ApplicationStatus::ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === ApplicationStatus::REJECTED;
    }

    public function getSubmittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): self
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }
}
