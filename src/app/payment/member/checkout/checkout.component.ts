import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/forms';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { PaymentApiService } from '../../payment-api.service';
import { CheckoutResponse, Payment } from '../../shared/payment.model';

@Component({
  selector: 'app-checkout',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './checkout.component.html',
  styleUrl: './checkout.component.scss',
})
export class CheckoutComponent implements OnInit {
  private paymentService = inject(PaymentApiService);

  // Selected plan to checkout
  selectedPlan = {
    subscriptionId: 42,
    name: 'Pro Fitness All-Access',
    durationDays: 30,
    price: 59.99,
    taxRate: 0.08,
    features: [
      'Unlimited gym floor & locker room access',
      'All group fitness & HIIT classes included',
      'Free body composition scan every 30 days',
      'Guest pass included (2 visits / month)',
    ],
  };

  // Payment form state
  cardHolder = 'Alex Morgan';
  cardNumber = '4242 •••• •••• 4242';
  expiry = '12/28';
  cvv = '389';
  billingZip = '90210';
  selectedMethod: 'card' | 'apple-pay' | 'google-pay' = 'card';

  isLoading = false;
  errorMessage: string | null = null;
  checkoutSuccess: CheckoutResponse | null = null;
  history: Payment[] = [];

  get subtotal(): number {
    return this.selectedPlan.price;
  }

  get tax(): number {
    return +(this.subtotal * this.selectedPlan.taxRate).toFixed(2);
  }

  get total(): number {
    return +(this.subtotal + this.tax).toFixed(2);
  }

  ngOnInit(): void {
    this.loadHistory();
  }

  loadHistory(): void {
    this.paymentService.getMemberHistory().subscribe((data) => {
      this.history = data;
    });
  }

  submitPayment(): void {
    this.isLoading = true;
    this.errorMessage = null;

    this.paymentService
      .processCheckout({
        subscriptionId: this.selectedPlan.subscriptionId,
        cardNumber: this.cardNumber,
        cardHolder: this.cardHolder,
        expiry: this.expiry,
        cvv: this.cvv,
      })
      .subscribe({
        next: (res) => {
          this.isLoading = false;
          this.checkoutSuccess = res;
          this.loadHistory();
        },
        error: (err) => {
          this.isLoading = false;
          this.errorMessage =
            err?.error?.error || 'Payment failed. Please check your card details and try again.';
        },
      });
  }

  closeSuccessModal(): void {
    this.checkoutSuccess = null;
  }
}
