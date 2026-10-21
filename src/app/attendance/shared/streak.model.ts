export type DayOfWeek =
  | 'MONDAY'
  | 'TUESDAY'
  | 'WEDNESDAY'
  | 'THURSDAY'
  | 'FRIDAY'
  | 'SATURDAY'
  | 'SUNDAY';

export interface CheckInHistoryItem {
  id: number;
  date: string;
  scannedAt: string;
}

export interface CheckInResult {
  success: boolean;
  message: string;
  data: {
    checkInId?: number;
    scannedAt?: string;
    currentStreak: number;
    streakIncremented: boolean;
    streakBroken: boolean;
    scheduleDays: string[];
  };
}

export interface MemberAttendanceHistory {
  totalAttendances: number;
  currentStreak: number;
  storedStreak: number;
  lastSuccessDate: string | null;
  history: CheckInHistoryItem[];
}

export interface WeeklySchedule {
  memberId: number;
  weekDays: DayOfWeek[];
  updatedAt?: string;
}

export interface StreakLeaderboardItem {
  memberId: number;
  memberName: string;
  streak: number;
  lastSuccessDate: string | null;
  badge?: string;
}
