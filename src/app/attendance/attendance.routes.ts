import { Routes } from '@angular/router';
import { WeeklyScheduleComponent } from './member/weekly-schedule/weekly-schedule.component';
import { QrCheckinComponent } from './member/qr-checkin/qr-checkin.component';
import { MyStreakComponent } from './member/my-streak/my-streak.component';
import { StreakOverviewComponent } from './admin/streak-overview/streak-overview.component';

export const attendanceRoutes: Routes = [
  {
    path: '',
    redirectTo: 'my-streak',
    pathMatch: 'full',
  },
  {
    path: 'schedule',
    component: WeeklyScheduleComponent,
    title: 'My Weekly Schedule | GymUp',
  },
  {
    path: 'check-in',
    component: QrCheckinComponent,
    title: 'QR Check-In Scanner | GymUp',
  },
  {
    path: 'my-streak',
    component: MyStreakComponent,
    title: 'My Streak & Attendance | GymUp',
  },
  {
    path: 'admin/streaks',
    component: StreakOverviewComponent,
    title: 'Attendance & Streaks Leaderboard | GymUp',
  },
];
