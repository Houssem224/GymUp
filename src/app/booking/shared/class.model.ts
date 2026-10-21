import { DayOfWeek } from '../../attendance/shared/streak.model';

export interface ClassSession {
  id: number;
  name: string;
  coachId: number;
  coachName: string;
  room: string;
  dayOfWeek: DayOfWeek;
  startTime: string;
  endTime: string;
  capacity: number;
  bookingCount: number;
  waitlistCount: number;
}

export interface CreateClassRequest {
  name: string;
  coachId: number;
  room: string;
  dayOfWeek: DayOfWeek;
  startTime: string;
  endTime: string;
  capacity: number;
}

export interface UpdateClassRequest {
  name?: string;
  coachId?: number;
  room?: string;
  dayOfWeek?: DayOfWeek;
  startTime?: string;
  endTime?: string;
  capacity?: number;
}