import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, catchError, of } from 'rxjs';
import {
  Plan,
  Subscription,
  CreatePlanRequest,
  UpdatePlanRequest
} from './shared/plan.model';

@Injectable({
  providedIn: 'root'
})
export class MembershipApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = 'http://localhost:8000/api';

  // Admin endpoints
  getPlans(): Observable<Plan[]> {
    return this.http.get<Plan[]>(`${this.baseUrl}/admin/plans`).pipe(
      catchError(() => {
        const fallback: Plan[] = [
          {
            id: 1,
            name: 'Basic',
            price: 29.99,
            durationDays: 30,
            accessScope: 'OPEN_GYM',
            maxMembers: null,
            isActive: true,
            currentMembersCount: 45
          },
          {
            id: 2,
            name: 'Premium',
            price: 59.99,
            durationDays: 30,
            accessScope: 'BOTH',
            maxMembers: 100,
            isActive: true,
            currentMembersCount: 78
          },
          {
            id: 3,
            name: 'Classes Only',
            price: 49.99,
            durationDays: 30,
            accessScope: 'CLASSES',
            maxMembers: 50,
            isActive: true,
            currentMembersCount: 32
          }
        ];
        return of(fallback);
      })
    );
  }

  createPlan(data: CreatePlanRequest): Observable<Plan> {
    return this.http.post<Plan>(`${this.baseUrl}/admin/plans`, data);
  }

  updatePlan(id: number, data: UpdatePlanRequest): Observable<Plan> {
    return this.http.patch<Plan>(`${this.baseUrl}/admin/plans/${id}`, data);
  }

  activatePlan(id: number): Observable<any> {
    return this.http.patch(`${this.baseUrl}/admin/plans/${id}/activate`, {});
  }

  deactivatePlan(id: number): Observable<any> {
    return this.http.patch(`${this.baseUrl}/admin/plans/${id}/deactivate`, {});
  }

  getSubscriptions(status?: string, planId?: number, limit?: number): Observable<Subscription[]> {
    let params = new HttpParams();
    if (status) params = params.set('status', status);
    if (planId) params = params.set('planId', planId);
    if (limit) params = params.set('limit', limit);

    return this.http.get<Subscription[]>(`${this.baseUrl}/admin/subscriptions`, { params });
  }

  getExpiringSubscriptions(days?: number): Observable<Subscription[]> {
    let params = new HttpParams();
    if (days) params = params.set('days', days);

    return this.http.get<Subscription[]>(`${this.baseUrl}/admin/plans/expiring`, { params });
  }

  // Receptionist endpoints
  getReceptionistPlans(): Observable<Plan[]> {
    return this.http.get<Plan[]>(`${this.baseUrl}/receptionist/plans`);
  }

  registerWalkInMember(data: { email: string; fullName: string; planId: number }): Observable<any> {
    return this.http.post(`${this.baseUrl}/receptionist/members/register`, data);
  }

  // Member endpoints
  getMemberPlans(): Observable<Plan[]> {
    return this.http.get<Plan[]>(`${this.baseUrl}/member/membership/plans`);
  }

  subscribeToPlan(planId: number): Observable<Subscription> {
    return this.http.post<Subscription>(`${this.baseUrl}/member/membership/subscribe`, { planId });
  }

  renewSubscription(planId: number): Observable<Subscription> {
    return this.http.post<Subscription>(`${this.baseUrl}/member/membership/renew`, { planId });
  }

  getSubscriptionStatus(): Observable<Subscription> {
    return this.http.get<Subscription>(`${this.baseUrl}/member/membership/status`);
  }

  getSubscriptionHistory(): Observable<Subscription[]> {
    return this.http.get<Subscription[]>(`${this.baseUrl}/member/membership/history`);
  }
}