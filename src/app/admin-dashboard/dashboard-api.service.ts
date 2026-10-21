import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, of } from 'rxjs';

export interface DashboardMetrics {
  activeMembers: number;
  totalMembers: number;
  classOccupancyRate: number;
  monthlyRevenue: {
    online: number;
    cash: number;
    total: number;
  };
  expiringMemberships: number;
  topStreaks: Array<{
    memberId: number;
    memberName: string;
    streak: number;
    lastSuccessDate: string;
  }>;
}

@Injectable({
  providedIn: 'root'
})
export class DashboardApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = 'http://localhost:8000/api';

  getDashboardMetrics(): Observable<DashboardMetrics> {
    return this.http.get<DashboardMetrics>(`${this.baseUrl}/admin/dashboard`).pipe(
      catchError(() => {
        const fallback: DashboardMetrics = {
          activeMembers: 156,
          totalMembers: 203,
          classOccupancyRate: 78.5,
          monthlyRevenue: {
            online: 8420.0,
            cash: 3150.0,
            total: 11570.0
          },
          expiringMemberships: 12,
          topStreaks: [
            { memberId: 101, memberName: 'Alexander Volkov', streak: 34, lastSuccessDate: '2026-10-21' },
            { memberId: 102, memberName: 'Jessica Chen', streak: 28, lastSuccessDate: '2026-10-21' },
            { memberId: 103, memberName: 'Marcus Aurelius', streak: 21, lastSuccessDate: '2026-10-20' },
            { memberId: 104, memberName: 'Sophia Taylor', streak: 16, lastSuccessDate: '2026-10-21' },
            { memberId: 105, memberName: 'David Beckham', streak: 12, lastSuccessDate: '2026-10-20' }
          ]
        };
        return of(fallback);
      })
    );
  }
}