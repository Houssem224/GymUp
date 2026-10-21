<?php

namespace App\Attendance\Entity;

use App\Attendance\Repository\CheckInRepository;
use App\Auth\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CheckInRepository::class)]
#[ORM\Table(name: 'check_ins')]
#[ORM\Index(name: 'idx_checkin_member_date', columns: ['member_id', 'check_in_date'])]
class CheckIn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'checkIns')]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $member = null;

    #[ORM\Column(name: 'check_in_date', type: 'date_immutable')]
    private \DateTimeImmutable $checkInDate;

    #[ORM\Column(name: 'scanned_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $scannedAt;

    public function __construct(User $member, ?\DateTimeImmutable $scannedAt = null)
    {
        $this->member = $member;
        $now = $scannedAt ?? new \DateTimeImmutable();
        $this->scannedAt = $now;
        $this->checkInDate = $now->setTime(0, 0, 0);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMember(): ?User
    {
        return $this->member;
    }

    public function setMember(?User $member): self
    {
        $this->member = $member;
        return $this;
    }

    public function getCheckInDate(): \DateTimeImmutable
    {
        return $this->checkInDate;
    }

    public function setCheckInDate(\DateTimeImmutable $checkInDate): self
    {
        $this->checkInDate = $checkInDate->setTime(0, 0, 0);
        return $this;
    }

    public function getScannedAt(): \DateTimeImmutable
    {
        return $this->scannedAt;
    }

    public function setScannedAt(\DateTimeImmutable $scannedAt): self
    {
        $this->scannedAt = $scannedAt;
        $this->checkInDate = $scannedAt->setTime(0, 0, 0);
        return $this;
    }
}
