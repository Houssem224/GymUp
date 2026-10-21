<?php

namespace App\Attendance\Entity;

use App\Attendance\Repository\ScheduleRepository;
use App\Auth\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
#[ORM\Table(name: 'schedules')]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'schedule', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $member = null;

    /**
     * Stored as JSON array, e.g. ["MONDAY", "WEDNESDAY", "FRIDAY"]
     * @var array<string>
     */
    #[ORM\Column(type: 'json')]
    private array $weekDays = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    /**
     * @return array<string>
     */
    public function getWeekDays(): array
    {
        return $this->weekDays;
    }

    /**
     * @param array<string> $weekDays
     */
    public function setWeekDays(array $weekDays): self
    {
        // Normalize weekdays to uppercase standard names (MONDAY, TUESDAY, etc.)
        $validDays = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];
        $normalized = [];

        foreach ($weekDays as $day) {
            $upper = strtoupper(trim((string)$day));
            if (in_array($upper, $validDays, true) && !in_array($upper, $normalized, true)) {
                $normalized[] = $upper;
            }
        }

        $this->weekDays = $normalized;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function includesDay(string $dayName): bool
    {
        return in_array(strtoupper($dayName), $this->weekDays, true);
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
