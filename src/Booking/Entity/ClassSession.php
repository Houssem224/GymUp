<?php

namespace App\Booking\Entity;

use App\Auth\Entity\User;
use App\Booking\Repository\ClassSessionRepository;
use App\Shared\Enum\BookingStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClassSessionRepository::class)]
#[ORM\Table(name: 'class_sessions')]
#[ORM\Index(name: 'idx_class_schedule', columns: ['day_of_week', 'start_time', 'end_time'])]
class ClassSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'coach_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $coach = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $room;

    #[ORM\Column(name: 'day_of_week', type: 'string', length: 20)]
    private string $dayOfWeek;

    #[ORM\Column(name: 'start_time', type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(name: 'end_time', type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: 'integer')]
    private int $capacity;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(mappedBy: 'classSession', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCoach(): ?User
    {
        return $this->coach;
    }

    public function setCoach(?User $coach): self
    {
        $this->coach = $coach;
        return $this;
    }

    public function getRoom(): string
    {
        return $this->room;
    }

    public function setRoom(string $room): self
    {
        $this->room = trim($room);
        return $this;
    }

    public function getDayOfWeek(): string
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(string $dayOfWeek): self
    {
        $this->dayOfWeek = strtoupper(trim($dayOfWeek));
        return $this;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = max(1, $capacity);
        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function getConfirmedBookingsCount(): int
    {
        $count = 0;
        foreach ($this->bookings as $b) {
            if ($b->getStatus() === BookingStatus::CONFIRMED) {
                $count++;
            }
        }
        return $count;
    }

    public function isFull(): bool
    {
        return $this->getConfirmedBookingsCount() >= $this->capacity;
    }

    /**
     * Checks if this class session overlaps with the given time range on the same day.
     */
    public function overlapsWith(string $dayOfWeek, \DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        if (strtoupper($this->dayOfWeek) !== strtoupper($dayOfWeek)) {
            return false;
        }

        $thisStart = $this->startTime->format('H:i');
        $thisEnd = $this->endTime->format('H:i');
        $checkStart = $start->format('H:i');
        $checkEnd = $end->format('H:i');

        // Overlap exists if max(start1, start2) < min(end1, end2)
        return max($thisStart, $checkStart) < min($thisEnd, $checkEnd);
    }
}
