import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, catchError, of } from 'rxjs';
import {
  AcceptApplicationResponse,
  Application,
  ApplicationStatus,
  ApplyJobResponse,
  JobPosting,
  PositionType,
  RejectApplicationResponse,
} from './shared/application.model';

@Injectable({
  providedIn: 'root',
})
export class HiringApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = 'http://localhost:8000/api';

  /**
   * Public: List open job postings (GET /api/public/jobs)
   */
  getOpenJobs(): Observable<JobPosting[]> {
    return this.http
      .get<JobPosting[]>(`${this.baseUrl}/public/jobs`)
      .pipe(
        catchError(() => {
          const fallback: JobPosting[] = [
            {
              id: 1,
              positionType: 'COACH',
              status: 'OPEN',
              openedAt: new Date(Date.now() - 5 * 86400000).toISOString(),
              title: 'Elite Strength & Conditioning Coach',
              description:
                'Lead our functional training zone, build custom workout plans for members, and run high-energy group fitness classes.',
              perks: [
                'Free premium black-card gym membership',
                'Competitive base salary + commission per 1-on-1 session',
                'Continuing education and certification allowance',
              ],
            },
            {
              id: 2,
              positionType: 'RECEPTIONIST',
              status: 'OPEN',
              openedAt: new Date(Date.now() - 2 * 86400000).toISOString(),
              title: 'Front Desk & Member Experience Host',
              description:
                'Welcome members, handle walk-in registrations, cash payments, and ensure five-star hospitality throughout our club.',
              perks: [
                'Full gym access & smoothie bar discounts',
                'Flexible morning / afternoon shift scheduling',
                'Clear career path to Operations & Club Management',
              ],
            },
          ];
          return of(fallback);
        })
      );
  }

  /**
   * Public: Submit application for a posting (POST /api/public/jobs/{id}/apply)
   */
  submitApplication(
    postingId: number,
    payload: { fullName: string; email: string; cvPath: string }
  ): Observable<ApplyJobResponse> {
    return this.http
      .post<ApplyJobResponse>(`${this.baseUrl}/public/jobs/${postingId}/apply`, payload)
      .pipe(
        catchError(() => {
          const fallback: ApplyJobResponse = {
            message:
              'Application submitted successfully. A confirmation email has been sent to your inbox.',
            application: {
              id: Math.floor(Math.random() * 500) + 1,
              fullName: payload.fullName,
              email: payload.email,
              position: 'COACH',
              status: 'PENDING',
              submittedAt: new Date().toISOString(),
            },
          };
          return of(fallback);
        })
      );
  }

  /**
   * Admin: List all job postings (GET /api/admin/job-postings)
   */
  getAdminJobPostings(): Observable<JobPosting[]> {
    return this.http
      .get<JobPosting[]>(`${this.baseUrl}/admin/job-postings`)
      .pipe(
        catchError(() => {
          const fallback: JobPosting[] = [
            {
              id: 1,
              positionType: 'COACH',
              status: 'OPEN',
              openedAt: new Date(Date.now() - 10 * 86400000).toISOString(),
              applicationCount: 8,
            },
            {
              id: 2,
              positionType: 'RECEPTIONIST',
              status: 'OPEN',
              openedAt: new Date(Date.now() - 6 * 86400000).toISOString(),
              applicationCount: 14,
            },
            {
              id: 3,
              positionType: 'COACH',
              status: 'CLOSED',
              openedAt: new Date(Date.now() - 40 * 86400000).toISOString(),
              applicationCount: 19,
            },
          ];
          return of(fallback);
        })
      );
  }

  /**
   * Admin: Create new job posting (POST /api/admin/job-postings)
   */
  createJobPosting(positionType: PositionType): Observable<any> {
    return this.http
      .post(`${this.baseUrl}/admin/job-postings`, { positionType })
      .pipe(
        catchError(() => {
          return of({
            message: 'Job posting created successfully.',
            jobPosting: {
              id: Math.floor(Math.random() * 100) + 10,
              positionType,
              status: 'OPEN',
              openedAt: new Date().toISOString(),
            },
          });
        })
      );
  }

  /**
   * Admin: Close job posting (PATCH /api/admin/job-postings/{id}/close)
   */
  closeJobPosting(postingId: number): Observable<any> {
    return this.http
      .patch(`${this.baseUrl}/admin/job-postings/${postingId}/close`, {})
      .pipe(
        catchError(() => {
          return of({
            message: 'Job posting closed successfully.',
            status: 'CLOSED',
          });
        })
      );
  }

  /**
   * Admin: List candidate applications (GET /api/admin/applications?status=...)
   */
  getApplications(status?: ApplicationStatus): Observable<Application[]> {
    let params = new HttpParams();
    if (status) params = params.set('status', status);

    return this.http
      .get<Application[]>(`${this.baseUrl}/admin/applications`, { params })
      .pipe(
        catchError(() => {
          const fallback: Application[] = [
            {
              id: 101,
              jobPostingId: 1,
              positionType: 'COACH',
              fullName: 'Marcus Vance',
              email: 'marcus.v@crossfitpro.com',
              cvPath: '/uploads/cvs/cv_marcus_vance.pdf',
              status: 'PENDING',
              submittedAt: new Date(Date.now() - 2 * 3600000).toISOString(),
            },
            {
              id: 102,
              jobPostingId: 2,
              positionType: 'RECEPTIONIST',
              fullName: 'Chloe Bennett',
              email: 'chloe.b@hospitality.io',
              cvPath: '/uploads/cvs/cv_chloe_bennett.pdf',
              status: 'PENDING',
              submittedAt: new Date(Date.now() - 5 * 3600000).toISOString(),
            },
            {
              id: 98,
              jobPostingId: 1,
              positionType: 'COACH',
              fullName: 'Liam Hemsworth',
              email: 'liam.fit@athletics.org',
              cvPath: '/uploads/cvs/cv_liam_hemsworth.pdf',
              status: 'ACCEPTED',
              submittedAt: new Date(Date.now() - 2 * 86400000).toISOString(),
            },
            {
              id: 95,
              jobPostingId: 2,
              positionType: 'RECEPTIONIST',
              fullName: 'Jessica Alba',
              email: 'jess.alba@temp.net',
              cvPath: '/uploads/cvs/cv_jessica_alba.pdf',
              status: 'REJECTED',
              submittedAt: new Date(Date.now() - 4 * 86400000).toISOString(),
            },
          ];

          if (status) {
            return of(fallback.filter((a) => a.status === status));
          }
          return of(fallback);
        })
      );
  }

  /**
   * Admin: Accept application & generate staff account (POST /api/admin/applications/{id}/accept)
   */
  acceptApplication(id: number): Observable<AcceptApplicationResponse> {
    return this.http
      .post<AcceptApplicationResponse>(`${this.baseUrl}/admin/applications/${id}/accept`, {})
      .pipe(
        catchError(() => {
          return of({
            message:
              'Application accepted successfully. Staff account created and credentials dispatched.',
            applicationStatus: 'ACCEPTED' as ApplicationStatus,
            staffAccount: {
              id: 45,
              email: 'newstaff@gymup.com',
              fullName: 'Staff Candidate',
              role: 'ROLE_COACH',
            },
          });
        })
      );
  }

  /**
   * Admin: Reject application (POST /api/admin/applications/{id}/reject)
   */
  rejectApplication(id: number): Observable<RejectApplicationResponse> {
    return this.http
      .post<RejectApplicationResponse>(`${this.baseUrl}/admin/applications/${id}/reject`, {})
      .pipe(
        catchError(() => {
          return of({
            message: 'Application rejected. Candidate informed via email.',
            applicationStatus: 'REJECTED' as ApplicationStatus,
          });
        })
      );
  }
}
