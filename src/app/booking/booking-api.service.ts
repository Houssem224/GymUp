import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, of } from 'rxjs';
import {
  ClassSession,
  CreateClassRequest,
  UpdateClassRequest,
  ClassBooking,
  BookClassResponse
} from './shared/class.model';
import { Booking, BookingStatus } from './shared/booking.model';

@Injectable({
  providedIn: 'root'
})
export class BookingApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = 'http://localhost:8000/api';

  // Admin endpoints
  getClasses(): Observable<ClassSession[]> {
    return this.http.get<ClassSession[]>(`${this.baseUrl}/admin/classes`).pipe(
      catchError(() => {
        const fallback: ClassSession[] = [
          {
            id: 1,
            name: 'Yoga Flow',
            coachId: 5,
            coachName: 'Sarah Johnson',
            room: 'Room A',
            dayOfWeek: 'MONDAY',
            startTime: '09:00',
            endTime: '10:00',
            capacity: 20,
            bookingCount: 15,
            waitlistCount: 2
          },
          {
            id: 2,
            name: 'HIIT Training',
            coachId: 6,
            coachName: 'Mike Smith',
            room: 'Room B',
            dayOfWeek: 'WEDNESDAY',
            startTime: '18:00',
            endTime: '19:00',
            capacity: 15,
            bookingCount: 15,
            waitlistCount: 5
          }
        ];
        return of(fallback);
      })
    );
  }

  createClass(data: CreateClassRequest): Observable<ClassSession> {
    return this.http.post<ClassSession>(`${this.baseUrl}/admin/classes`, data);
  }

  updateClass(id: number, data: UpdateClassRequest): Observable<ClassSession> {
    return this.http.patch<ClassSession>(`${this.baseUrl}/admin/classes/${id}`, data);
  }

  deleteClass(id: number): Observable<any> {
    return this.http.delete(`${this.baseUrl}/admin/classes/${id}`);
  }

  getClassBookings(classId: number): Observable<ClassBooking[]> {
    return this.http.get<ClassBooking[]>(`${this.baseUrl}/admin/classes/${classId}/bookings`);
  }

  getClassWaitlist(classId: number): Observable<ClassBooking[]> {
    return this.http.get<ClassBooking[]>(`${this.baseUrl}/admin/classes/${classId}/waitlist`);
  }

  // Coach endpoints
  getCoachSchedule(): Observable<ClassSession[]> {
    return this.http.get<ClassSession[]>(`${this.baseUrl}/coach/schedule`);
  }

  getClassMembers(classId: number): Observable<ClassBooking[]> {
    return this.http.get<ClassBooking[]>(`${this.baseUrl}/coach/classes/${classId}/members`);
  }

  // Member endpoints
  getMemberClasses(): Observable<ClassSession[]> {
    return this.http.get<ClassSession[]>(`${this.baseUrl}/member/classes`);
  }

  bookClass(classId: number): Observable<BookClassResponse> {
    return this.http.post<BookClassResponse>(`${this.baseUrl}/member/classes/${classId}/book`, {});
  }

  cancelBooking(bookingId: number): Observable<any> {
    return this.http.post(`${this.baseUrl}/member/bookings/${bookingId}/cancel`, {});
  }

  getMemberBookings(): Observable<Booking[]> {
    return this.http.get<Booking[]>(`${this.baseUrl}/member/bookings`);
  }
}