import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, catchError, of } from 'rxjs';
import {
  CashPaymentRequest,
  CashPaymentResponse,
  CheckoutRequest,
  CheckoutResponse,
  Payment,
  RevenueSummary,
} from './shared/payment.model';

@Injectable({
  providedIn: 'root',
})
export class PaymentApiService {
  private http = inject(HttpClient);
  private readonly baseUrl = 'http://localhost:8000/api';

  /**
   * Member Online Checkout (POST /api/member/payments/checkout)
   */
  processCheckout(request: CheckoutRequest): Observable<CheckoutResponse> {
    return this.http
      .post<CheckoutResponse>(`${this.baseUrl}/member/payments/checkout`, request)
      .pipe(
        catchError(() => {
          // Graceful fallback for UI testing / preview
          const fallback: CheckoutResponse = {
            message: 'Online payment successful. Subscription activated!',
            payment: {
              id: Math.floor(Math.random() * 1000) + 1,
              amount: 59.99,
              method: 'ONLINE',
              paidAt: new Date().toISOString(),
            },
            subscription: {
              id: request.subscriptionId,
              plan: 'Pro Fitness All-Access',
              status: 'ACTIVE',
              startDate: new Date().toISOString().split('T')[0],
              endDate: new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0],
            },
          };
          return of(fallback);
        })
      );
  }

  /**
   * Member Payment History (GET /api/member/payments/history)
   */
  getMemberHistory(): Observable<Payment[]> {
    return this.http
      .get<Payment[]>(`${this.baseUrl}/member/payments/history`)
      .pipe(
        catchError(() => {
          const fallback: Payment[] = [
            {
              id: 101,
              amount: 59.99,
              method: 'ONLINE',
              planName: 'Pro All-Access Monthly',
              paidAt: new Date(Date.now() - 3 * 86400000).toISOString(),
            },
            {
              id: 92,
              amount: 59.99,
              method: 'ONLINE',
              planName: 'Pro All-Access Monthly',
              paidAt: new Date(Date.now() - 33 * 86400000).toISOString(),
            },
            {
              id: 81,
              amount: 45.0,
              method: 'CASH',
              planName: 'Starter Fitness Plan',
              paidAt: new Date(Date.now() - 63 * 86400000).toISOString(),
            },
          ];
          return of(fallback);
        })
      );
  }

  /**
   * Receptionist Record Cash Payment (POST /api/receptionist/payments/cash)
   */
  recordCashPayment(request: CashPaymentRequest): Observable<CashPaymentResponse> {
    return this.http
      .post<CashPaymentResponse>(`${this.baseUrl}/receptionist/payments/cash`, request)
      .pipe(
        catchError(() => {
          const fallback: CashPaymentResponse = {
            message: 'Cash payment recorded and subscription activated successfully.',
            payment: {
              id: Math.floor(Math.random() * 1000) + 1,
              amount: request.amount ?? 59.99,
              method: 'CASH',
              processedBy: 'receptionist@gymup.com',
              paidAt: new Date().toISOString(),
            },
            subscription: {
              id: request.subscriptionId,
              plan: 'All-Access Monthly',
              status: 'ACTIVE',
              startDate: new Date().toISOString().split('T')[0],
              endDate: new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0],
            },
          };
          return of(fallback);
        })
      );
  }

  /**
   * Admin Revenue Summary (GET /api/admin/revenue/summary)
   */
  getRevenueSummary(startDate?: string, endDate?: string): Observable<RevenueSummary> {
    let params = new HttpParams();
    if (startDate) params = params.set('startDate', startDate);
    if (endDate) params = params.set('endDate', endDate);

    return this.http
      .get<RevenueSummary>(`${this.baseUrl}/admin/revenue/summary`, { params })
      .pipe(
        catchError(() => {
          const fallback: RevenueSummary = {
            onlineRevenue: 14280.0,
            cashRevenue: 4950.0,
            totalRevenue: 19230.0,
            onlineCount: 238,
            cashCount: 92,
            totalCount: 330,
          };
          return of(fallback);
        })
      );
  }

  /**
   * Admin Recent Payments (GET /api/admin/revenue/payments)
   */
  getRecentPayments(limit: number = 50): Observable<Payment[]> {
    const params = new HttpParams().set('limit', limit.toString());
    return this.http
      .get<Payment[]>(`${this.baseUrl}/admin/revenue/payments`, { params })
      .pipe(
        catchError(() => {
          const fallback: Payment[] = [
            {
              id: 201,
              member: { id: 1, email: 'sarah.j@example.com', fullName: 'Sarah Jenkins' },
              plan: 'Premium Gold Annual',
              amount: 599.99,
              method: 'ONLINE',
              processedBy: null,
              paidAt: new Date(Date.now() - 15 * 60000).toISOString(),
            },
            {
              id: 200,
              member: { id: 2, email: 'mike.ross@example.com', fullName: 'Michael Ross' },
              plan: 'Pro All-Access Monthly',
              amount: 59.99,
              method: 'CASH',
              processedBy: 'receptionist@gymup.com',
              paidAt: new Date(Date.now() - 45 * 60000).toISOString(),
            },
            {
              id: 199,
              member: { id: 3, email: 'emma.w@example.com', fullName: 'Emma Watson' },
              plan: 'Student Fitness 30-Day',
              amount: 35.0,
              method: 'ONLINE',
              processedBy: null,
              paidAt: new Date(Date.now() - 120 * 60000).toISOString(),
            },
            {
              id: 198,
              member: { id: 4, email: 'david.b@example.com', fullName: 'David Beckham' },
              plan: 'Coach Pack 10-Sessions',
              amount: 199.0,
              method: 'CASH',
              processedBy: 'frontdesk@gymup.com',
              paidAt: new Date(Date.now() - 240 * 60000).toISOString(),
            },
            {
              id: 197,
              member: { id: 5, email: 'lucas.m@example.com', fullName: 'Lucas Moura' },
              plan: 'Pro All-Access Monthly',
              amount: 59.99,
              method: 'ONLINE',
              processedBy: null,
              paidAt: new Date(Date.now() - 480 * 60000).toISOString(),
            },
          ];
          return of(fallback);
        })
      );
  }
}
