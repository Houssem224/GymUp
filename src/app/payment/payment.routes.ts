import { Routes } from '@angular/router';
import { CheckoutComponent } from './member/checkout/checkout.component';
import { CashPaymentComponent } from './receptionist/cash-payment/cash-payment.component';
import { RevenueReportComponent } from './admin/revenue-report/revenue-report.component';

export const paymentRoutes: Routes = [
  {
    path: '',
    redirectTo: 'checkout',
    pathMatch: 'full',
  },
  {
    path: 'checkout',
    component: CheckoutComponent,
    title: 'Checkout & Payment | GymUp',
  },
  {
    path: 'receptionist/cash',
    component: CashPaymentComponent,
    title: 'Front Desk Cash Register | GymUp',
  },
  {
    path: 'admin/revenue',
    component: RevenueReportComponent,
    title: 'Revenue & Reports | GymUp',
  },
];
