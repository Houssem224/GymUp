import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AttendanceApiService } from '../../attendance-api.service';
import { StreakLeaderboardItem } from '../../shared/streak.model';

@Component({
  selector: 'app-streak-overview',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './streak-overview.component.html',
  styleUrl: './streak-overview.component.scss',
})
export class StreakOverviewComponent implements OnInit {
  private attendanceService = inject(AttendanceApiService);

  leaderboard: StreakLeaderboardItem[] = [];
  searchTerm = '';
  isLoading = true;
  nudgeNotification: string | null = null;

  get filteredLeaderboard(): StreakLeaderboardItem[] {
    const q = this.searchTerm.toLowerCase();
    if (!q) return this.leaderboard;
    return this.leaderboard.filter((item) =>
      item.memberName.toLowerCase().includes(q)
    );
  }

  get averageTopStreak(): number {
    if (this.leaderboard.length === 0) return 0;
    const total = this.leaderboard.reduce((acc, curr) => acc + curr.streak, 0);
    return Math.round(total / this.leaderboard.length);
  }

  ngOnInit(): void {
    this.attendanceService.getLeaderboard().subscribe((data) => {
      this.leaderboard = data;
      this.isLoading = false;
    });
  }

  sendNudge(item: StreakLeaderboardItem): void {
    this.nudgeNotification = `Motivational push notification sent to ${item.memberName}! 🔥`;
    setTimeout(() => (this.nudgeNotification = null), 4000);
  }
}
