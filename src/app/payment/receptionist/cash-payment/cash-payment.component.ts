import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PaymentApiService } from '../../payment-api.service';
import { CashPaymentResponse } from '../../shared/payment.model';

interface MemberLookup {
  id: number;
  fullName: string;
  email: string;
  phone: string;
  pendingSubscription: {
    id: number;
    planName: string;
    expectedPrice: number;
  } | null;
}

@Component({
  selector: 'app-cash-payment',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './cash-payment.component.html',
  styleUrl: './cash-payment.component.scss',
})
export class CashPaymentComponent {
  private paymentService = inject(PaymentApiService);

  // Search member input
  searchQuery = 'David';
  selectedMember: MemberLookup | null = {
    id: 14,
    fullName: 'David Miller',
    email: 'david.m@example.com',
    phone: '+1 (555) 234-5678',
    pendingSubscription: {
      id: 88,
      planName: 'Pro All-Access Monthly',
      expectedPrice: 59.99,
    },
  };

  // Cash payment fields
  cashReceived: number = 60.0;
  receptionistNotes: string = '';
  isProcessing = false;
  errorMessage: string | null = null;
  paymentSuccess: CashPaymentResponse | null = null;

  // History of receipts recorded today in this session
  todayReceipts: Array<{
    id: number;
    member: string;
    plan: string;
    amount: number;
    time: string;
  }> = [
    {
      id: 204,
      member: 'Sarah Connors',
      plan: 'Starter Fitness Plan',
      amount: 45.0,
      time: '10:15 AM',
    },
    {
      id: 203,
      member: 'Lucas Graham',
      plan: 'Coach 10-Pack Session',
      amount: 199.0,
      time: '09:40 AM',
    },
  ];

  get expectedAmount(): number {
    return this.selectedMember?.pendingSubscription?.expectedPrice ?? 0;
  }

  get changeDue(): number {
    if (!this.cashReceived || this.cashReceived < this.expectedAmount) return 0;
    return +(this.cashReceived - this.expectedAmount).toFixed(2);
  }

  get isUnderpaid(): boolean {
    return this.cashReceived < this.expectedAmount;
  }

  recordCash(): void {
    if (!this.selectedMember || !this.selectedMember.pendingSubscription) {
      this.errorMessage = 'Please select a member with a pending subscription.';
      return;
    }

    if (this.isUnderpaid) {
      this.errorMessage = `Cash received ($${this.cashReceived}) is lower than the plan price ($${this.expectedAmount}).`;
      return;
    }

    this.isProcessing = true;
    this.errorMessage = null;

    this.paymentService
      .recordCashPayment({
        memberId: this.selectedMember.id,
        subscriptionId: this.selectedMember.pendingSubscription.id,
        amount: this.cashReceived,
      })
      .subscribe({
        next: (res) => {
          this.isProcessing = false;
          this.paymentSuccess = res;

          this.todayReceipts.unshift({
            id: res.payment.id,
            member: this.selectedMember!.fullName,
            plan: res.subscription.plan,
            amount: res.payment.amount,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
          });
        },
        error: (err) => {
          this.isProcessing = false;
          this.errorMessage =
            err?.error?.error || 'Failed to record cash payment. Please retry.';
        },
      });
  }

  resetForm(): void {
    this.paymentSuccess = null;
    this.cashReceived = 0;
    this.selectedMember = null;
  }

  quickSelectPreset(memberId: number): void {
    if (memberId === 14) {
      this.selectedMember = {
        id: 14,
        fullName: 'David Miller',
        email: 'david.m@example.com',
        phone: '+1 (555) 234-5678',
        pendingSubscription: {
          id: 88,
          planName: 'Pro All-Access Monthly',
          expectedPrice: 59.99,
        },
      };
      this.cashReceived = 60.0;
    } else {
      this.selectedMember = {
        id: 22,
        fullName: 'Elena Rostova',
        email: 'elena.r@example.com',
        phone: '+1 (555) 876-5432',
        pendingSubscription: {
          id: 89,
          planName: 'Annual VIP All-Inclusive',
          expectedPrice: 499.0,
        },
      };
      this.cashReceived = 500.0;
    }
  }
}
