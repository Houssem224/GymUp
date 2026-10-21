<?php

namespace App\Hiring\Entity;

use App\Hiring\Repository\JobPostingRepository;
use App\Shared\Enum\JobPostingStatus;
use App\Shared\Enum\PositionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobPostingRepository::class)]
#[ORM\Table(name: 'job_postings')]
class JobPosting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'position_type', type: 'string', enumType: PositionType::class)]
    private PositionType $positionType;

    #[ORM\Column(type: 'string', enumType: JobPostingStatus::class)]
    private JobPostingStatus $status = JobPostingStatus::OPEN;

    #[ORM\Column(name: 'opened_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $openedAt;

    /**
     * @var Collection<int, Application>
     */
    #[ORM\OneToMany(mappedBy: 'jobPosting', targetEntity: Application::class, cascade: ['persist', 'remove'])]
    private Collection $applications;

    public function __construct(PositionType $positionType = PositionType::COACH)
    {
        $this->positionType = $positionType;
        $this->openedAt = new \DateTimeImmutable();
        $this->applications = new ArrayCollection();
        $this->status = JobPostingStatus::OPEN;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPositionType(): PositionType
    {
        return $this->positionType;
    }

    public function setPositionType(PositionType $positionType): self
    {
        $this->positionType = $positionType;
        return $this;
    }

    public function getStatus(): JobPostingStatus
    {
        return $this->status;
    }

    public function setStatus(JobPostingStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isOpen(): bool
    {
        return $this->status === JobPostingStatus::OPEN;
    }

    public function getOpenedAt(): \DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function setOpenedAt(\DateTimeImmutable $openedAt): self
    {
        $this->openedAt = $openedAt;
        return $this;
    }

    /**
     * @return Collection<int, Application>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(Application $application): self
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setJobPosting($this);
        }

        return $this;
    }
}
