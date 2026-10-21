<?php

namespace App\Booking\Entity;

use App\Auth\Entity\User;
use App\Booking\Repository\BookingRepository;
use App\Shared\Enum\BookingStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
#[ORM\Index(name: 'idx_booking_member_status', columns: ['member_id', 'status'])]
#[ORM\Index(name: 'idx_booking_class_status', columns: ['class_session_id', 'status'])]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $member = null;

    #[ORM\ManyToOne(targetEntity: ClassSession::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(name: 'class_session_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ClassSession $classSession = null;

    #[ORM\Column(type: 'string', enumType: BookingStatus::class)]
    private BookingStatus $status = BookingStatus::CONFIRMED;

    #[ORM\Column(name: 'booked_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $bookedAt;

    public function __construct(User $member, ClassSession $classSession, BookingStatus $status = BookingStatus::CONFIRMED)
    {
        $this->member = $member;
        $this->classSession = $classSession;
        $this->status = $status;
        $this->bookedAt = new \DateTimeImmutable();
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

    public function getClassSession(): ?ClassSession
    {
        return $this->classSession;
    }

    public function setClassSession(?ClassSession $classSession): self
    {
        $this->classSession = $classSession;
        return $this;
    }

    public function getStatus(): BookingStatus
    {
        return $this->status;
    }

    public function setStatus(BookingStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::CONFIRMED;
    }

    public function isWaitlist(): bool
    {
        return $this->status === BookingStatus::WAITLIST;
    }

    public function isCancelled(): bool
    {
        return $this->status === BookingStatus::CANCELLED;
    }

    public function getBookedAt(): \DateTimeImmutable
    {
        return $this->bookedAt;
    }

    public function setBookedAt(\DateTimeImmutable $bookedAt): self
    {
        $this->bookedAt = $bookedAt;
        return $this;
    }
}
