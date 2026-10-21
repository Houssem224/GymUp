import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AttendanceApiService } from '../../attendance-api.service';
import { DayOfWeek } from '../../shared/streak.model';

interface DayOption {
  day: DayOfWeek;
  label: string;
  short: string;
  selected: boolean;
}

@Component({
  selector: 'app-weekly-schedule',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './weekly-schedule.component.html',
  styleUrl: './weekly-schedule.component.scss',
})
export class WeeklyScheduleComponent implements OnInit {
  private attendanceService = inject(AttendanceApiService);

  days: DayOption[] = [
    { day: 'MONDAY', label: 'Monday', short: 'Mon', selected: false },
    { day: 'TUESDAY', label: 'Tuesday', short: 'Tue', selected: false },
    { day: 'WEDNESDAY', label: 'Wednesday', short: 'Wed', selected: false },
    { day: 'THURSDAY', label: 'Thursday', short: 'Thu', selected: false },
    { day: 'FRIDAY', label: 'Friday', short: 'Fri', selected: false },
    { day: 'SATURDAY', label: 'Saturday', short: 'Sat', selected: false },
    { day: 'SUNDAY', label: 'Sunday', short: 'Sun', selected: false },
  ];

  isSaving = false;
  successMessage: string | null = null;
  errorMessage: string | null = null;

  get selectedCount(): number {
    return this.days.filter((d) => d.selected).length;
  }

  get targetCommitment(): string {
    const c = this.selectedCount;
    if (c === 0) return 'No days selected (streaks inactive)';
    if (c <= 2) return 'Casual & Maintenance';
    if (c <= 4) return 'Optimal Muscle Building & Fitness';
    return 'Elite Athlete Commitment';
  }

  ngOnInit(): void {
    this.attendanceService.getSchedule().subscribe((res) => {
      const activeDays = res?.weekDays || [];
      this.days.forEach((d) => {
        d.selected = activeDays.includes(d.day);
      });
    });
  }

  toggleDay(day: DayOption): void {
    day.selected = !day.selected;
    this.successMessage = null;
  }

  saveSchedule(): void {
    const selectedDays = this.days.filter((d) => d.selected).map((d) => d.day);

    if (selectedDays.length === 0) {
      if (!confirm('You have selected 0 days. This pauses streak tracking. Continue?')) {
        return;
      }
    }

    this.isSaving = true;
    this.errorMessage = null;

    this.attendanceService.updateSchedule(selectedDays).subscribe({
      next: (res) => {
        this.isSaving = false;
        this.successMessage =
          'Your weekly gym attendance schedule is saved! Showing up on these days will build your streak.';
        setTimeout(() => (this.successMessage = null), 5000);
      },
      error: (err) => {
        this.isSaving = false;
        this.errorMessage =
          err?.error?.error || 'Failed to update schedule. Please try again.';
      },
    });
  }
}
