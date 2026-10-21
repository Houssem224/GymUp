import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PaymentApiService } from '../../payment-api.service';
import { Payment, RevenueSummary } from '../../shared/payment.model';

@Component({
  selector: 'app-revenue-report',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './revenue-report.component.html',
  styleUrl: './revenue-report.component.scss',
})
export class RevenueReportComponent implements OnInit {
  private paymentService = inject(PaymentApiService);

  // Filter state
  selectedPeriod: 'month' | 'quarter' | 'year' | 'custom' = 'month';
  startDate: string = '';
  endDate: string = '';
  methodFilter: 'ALL' | 'ONLINE' | 'CASH' = 'ALL';
  searchTerm: string = '';

  isLoading = true;
  summary: RevenueSummary = {
    onlineRevenue: 0,
    cashRevenue: 0,
    totalRevenue: 0,
    onlineCount: 0,
    cashCount: 0,
    totalCount: 0,
  };

  payments: Payment[] = [];

  get onlinePercent(): number {
    if (!this.summary.totalRevenue) return 0;
    return Math.round((this.summary.onlineRevenue / this.summary.totalRevenue) * 100);
  }

  get cashPercent(): number {
    if (!this.summary.totalRevenue) return 0;
    return 100 - this.onlinePercent;
  }

  get filteredPayments(): Payment[] {
    return this.payments.filter((p) => {
      const matchesMethod =
        this.methodFilter === 'ALL' || p.method === this.methodFilter;
      const query = this.searchTerm.toLowerCase();
      const matchesSearch =
        !query ||
        p.member?.fullName?.toLowerCase().includes(query) ||
        p.member?.email?.toLowerCase().includes(query) ||
        p.plan?.toLowerCase().includes(query) ||
        p.planName?.toLowerCase().includes(query);
      return matchesMethod && matchesSearch;
    });
  }

  ngOnInit(): void {
    this.setPeriod('month');
  }

  setPeriod(period: 'month' | 'quarter' | 'year' | 'custom'): void {
    this.selectedPeriod = period;
    const now = new Date();

    if (period === 'month') {
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
      this.startDate = firstDay.toISOString().split('T')[0];
      this.endDate = now.toISOString().split('T')[0];
    } else if (period === 'quarter') {
      const threeMonthsAgo = new Date(now.getTime() - 90 * 86400000);
      this.startDate = threeMonthsAgo.toISOString().split('T')[0];
      this.endDate = now.toISOString().split('T')[0];
    } else if (period === 'year') {
      const startOfYear = new Date(now.getFullYear(), 0, 1);
      this.startDate = startOfYear.toISOString().split('T')[0];
      this.endDate = now.toISOString().split('T')[0];
    }

    this.loadData();
  }

  loadData(): void {
    this.isLoading = true;
    this.paymentService.getRevenueSummary(this.startDate, this.endDate).subscribe((res) => {
      this.summary = res;
    });

    this.paymentService.getRecentPayments(50).subscribe((res) => {
      this.payments = res;
      this.isLoading = false;
    });
  }

  exportCsv(): void {
    const headers = ['ID,Date,Member,Email,Plan,Method,Amount,ProcessedBy'];
    const rows = this.filteredPayments.map((p) => [
      p.id,
      p.paidAt,
      `"${p.member?.fullName || 'N/A'}"`,
      p.member?.email || 'N/A',
      `"${p.plan || p.planName || 'N/A'}"`,
      p.method,
      p.amount,
      p.processedBy || 'Stripe/System',
    ].join(','));

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers, ...rows].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `GymUp_Revenue_Report_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
}
