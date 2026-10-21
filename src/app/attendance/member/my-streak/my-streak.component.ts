import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AttendanceApiService } from '../../attendance-api.service';
import { MemberAttendanceHistory } from '../../shared/streak.model';

@Component({
  selector: 'app-my-streak',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './my-streak.component.html',
  styleUrl: './my-streak.component.scss',
})
export class MyStreakComponent implements OnInit {
  private attendanceService = inject(AttendanceApiService);

  isLoading = true;
  historyData: MemberAttendanceHistory = {
    totalAttendances: 0,
    currentStreak: 0,
    storedStreak: 0,
    lastSuccessDate: null,
    history: [],
  };

  get streakTier(): string {
    const s = this.historyData.currentStreak;
    if (s === 0) return 'Get Started Today!';
    if (s < 3) return 'Sparking Up 🔥';
    if (s < 7) return 'Consistent Warrior ⚔️';
    if (s < 14) return 'Beast Mode ⚡';
    if (s < 30) return 'Iron Legend 👑';
    return 'Titan of Fitness 🏆';
  }

  ngOnInit(): void {
    this.attendanceService.getAttendanceHistory(20).subscribe((res) => {
      this.historyData = res;
      this.isLoading = false;
    });
  }
}
