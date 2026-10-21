export type BookingStatus = 'CONFIRMED' | 'WAITLIST' | 'CANCELLED';

export interface Booking {
  id: number;
  classId: number;
  className: string;
  memberId: number;
  memberName: string;
  memberEmail: string;
  status: BookingStatus;
  bookedAt: string;
  waitlistPosition?: number;
}

export interface ClassBooking {
  bookingId: number;
  memberId: number;
  memberName: string;
  memberEmail: string;
  status: BookingStatus;
  bookedAt: string;
  waitlistPosition?: number;
}

export interface BookClassResponse {
  message: string;
  booking: {
    id: number;
    classId: number;
    className: string;
    status: BookingStatus;
    bookedAt: string;
    waitlistPosition?: number;
  };
}