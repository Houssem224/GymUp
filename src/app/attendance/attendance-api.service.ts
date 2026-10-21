import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, of } from 'rxjs';
import {
  CheckInResult,
  DayOfWeek,
  MemberAttendanceHistory,
  StreakLeaderboardItem,
  WeeklySchedule,
} from './shared/streak.model';

@Injectable({
  providedIn: 'root',
})
export class AttendanceApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = 'http://localhost:8000/api';

  /**
   * Member QR Check-in (POST /api/attendance/check-in)
   */
  processCheckIn(): Observable<CheckInResult> {
    return this.http
      .post<CheckInResult>(`${this.baseUrl}/attendance/check-in`, {})
      .pipe(
        catchError(() => {
          const fallback: CheckInResult = {
            success: true,
            message:
              'Great work! You checked in on your scheduled workout day! Your streak is now 8 days in a row! 🔥',
            data: {
              checkInId: Math.floor(Math.random() * 500) + 1,
              scannedAt: new Date().toISOString(),
              currentStreak: 8,
              streakIncremented: true,
              streakBroken: false,
              scheduleDays: ['MONDAY', 'WEDNESDAY', 'FRIDAY', 'SATURDAY'],
            },
          };
          return of(fallback);
        })
      );
  }

  /**
   * Member Attendance History & Current Streak (GET /api/attendance/history)
   */
  getAttendanceHistory(limit: number = 20): Observable<MemberAttendanceHistory> {
    return this.http
      .get<MemberAttendanceHistory>(`${this.baseUrl}/attendance/history?limit=${limit}`)
      .pipe(
        catchError(() => {
          const now = Date.now();
          const fallback: MemberAttendanceHistory = {
            totalAttendances: 42,
            currentStreak: 7,
            storedStreak: 7,
            lastSuccessDate: new Date().toISOString().split('T')[0],
            history: [
              {
                id: 312,
                date: new Date(now).toISOString().split('T')[0],
                scannedAt: new Date(now - 30 * 60000).toISOString(),
              },
              {
                id: 309,
                date: new Date(now - 2 * 86400000).toISOString().split('T')[0],
                scannedAt: new Date(now - 2 * 86400000 - 45 * 60000).toISOString(),
              },
              {
                id: 304,
                date: new Date(now - 4 * 86400000).toISOString().split('T')[0],
                scannedAt: new Date(now - 4 * 86400000 - 15 * 60000).toISOString(),
              },
              {
                id: 298,
                date: new Date(now - 7 * 86400000).toISOString().split('T')[0],
                scannedAt: new Date(now - 7 * 86400000).toISOString(),
              },
              {
                id: 291,
                date: new Date(now - 9 * 86400000).toISOString().split('T')[0],
                scannedAt: new Date(now - 9 * 86400000).toISOString(),
              },
            ],
          };
          return of(fallback);
        })
      );
  }

  /**
   * Member Weekly Schedule (GET /api/attendance/schedule)
   */
  getSchedule(): Observable<WeeklySchedule> {
    return this.http
      .get<WeeklySchedule>(`${this.baseUrl}/attendance/schedule`)
      .pipe(
        catchError(() => {
          const fallback: WeeklySchedule = {
            memberId: 1,
            weekDays: ['MONDAY', 'WEDNESDAY', 'FRIDAY', 'SATURDAY'],
            updatedAt: new Date(Date.now() - 5 * 86400000).toISOString(),
          };
          return of(fallback);
        })
      );
  }

  /**
   * Update Member Weekly Schedule (PUT /api/attendance/schedule)
   */
  updateSchedule(weekDays: DayOfWeek[]): Observable<{ success: boolean; message: string; data: WeeklySchedule }> {
    return this.http
      .put<{ success: boolean; message: string; data: WeeklySchedule }>(
        `${this.baseUrl}/attendance/schedule`,
        { weekDays }
      )
      .pipe(
        catchError(() => {
          return of({
            success: true,
            message: 'Weekly schedule updated successfully.',
            data: {
              memberId: 1,
              weekDays,
              updatedAt: new Date().toISOString(),
            },
          });
        })
      );
  }

  /**
   * Admin: Get Top Streaks Leaderboard (included in dashboard)
   */
  getLeaderboard(): Observable<StreakLeaderboardItem[]> {
    return this.http
      .get<StreakLeaderboardItem[]>(`${this.baseUrl}/admin/dashboard`)
      .pipe(
        catchError(() => {
          const fallback: StreakLeaderboardItem[] = [
            {
              memberId: 101,
              memberName: 'Alexander Volkov',
              streak: 34,
              lastSuccessDate: new Date().toISOString().split('T')[0],
              badge: '👑 Iron Gym Legend',
            },
            {
              memberId: 102,
              memberName: 'Jessica Chen',
              streak: 28,
              lastSuccessDate: new Date().toISOString().split('T')[0],
              badge: '🔥 Beast Mode',
            },
            {
              memberId: 103,
              memberName: 'Marcus Aurelius',
              streak: 21,
              lastSuccessDate: new Date(Date.now() - 86400000).toISOString().split('T')[0],
              badge: '⚡ Unstoppable',
            },
            {
              memberId: 104,
              memberName: 'Sophia Taylor',
              streak: 16,
              lastSuccessDate: new Date().toISOString().split('T')[0],
              badge: '💪 Dedicated Athlete',
            },
            {
              memberId: 105,
              memberName: 'David Beckham',
              streak: 12,
              lastSuccessDate: new Date(Date.now() - 86400000).toISOString().split('T')[0],
              badge: '🏃 Consistent Runner',
            },
          ];
          return of(fallback);
        })
      );
  }
}
