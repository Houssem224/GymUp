import { Routes } from '@angular/router';
import { roleGuard } from './core/guards/role.guard';
import { LoginComponent } from './auth/login/login.component';
import { RegisterComponent } from './auth/register/register.component';
import { AdminLayoutComponent } from './core/layout/admin-layout/admin-layout.component';
import { MemberLayoutComponent } from './core/layout/member-layout/member-layout.component';
import { CoachLayoutComponent } from './core/layout/coach-layout/coach-layout.component';
import { ReceptionistLayoutComponent } from './core/layout/receptionist-layout/receptionist-layout.component';

export const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'login', component: LoginComponent },
  { path: 'register', component: RegisterComponent },
  {
    path: 'admin',
    component: AdminLayoutComponent,
    canActivate: [roleGuard],
    data: { role: 'ROLE_ADMIN' },
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      { path: 'dashboard', loadComponent: () => import('./admin-dashboard/dashboard-home/dashboard-home.component').then(m => m.DashboardHomeComponent) },
      { path: 'plans', loadComponent: () => import('./membership/admin/plan-list/plan-list.component').then(m => m.PlanListComponent) },
      { path: 'plans/new', loadComponent: () => import('./membership/admin/plan-form/plan-form.component').then(m => m.PlanFormComponent) },
      { path: 'plans/:id/edit', loadComponent: () => import('./membership/admin/plan-form/plan-form.component').then(m => m.PlanFormComponent) },
      { path: 'classes', loadComponent: () => import('./booking/admin/class-list/class-list.component').then(m => m.ClassListComponent) },
      { path: 'classes/new', loadComponent: () => import('./booking/admin/class-form/class-form.component').then(m => m.ClassFormComponent) },
      { path: 'classes/:id/edit', loadComponent: () => import('./booking/admin/class-form/class-form.component').then(m => m.ClassFormComponent) },
      { path: 'hiring', loadComponent: () => import('./hiring/admin/posting-list/posting-list.component').then(m => m.PostingListComponent) },
      { path: 'applications', loadComponent: () => import('./hiring/admin/application-review/application-review.component').then(m => m.ApplicationReviewComponent) },
      { path: 'revenue', loadComponent: () => import('./payment/admin/revenue-report/revenue-report.component').then(m => m.RevenueReportComponent) },
      { path: 'streaks', loadComponent: () => import('./attendance/admin/streak-overview/streak-overview.component').then(m => m.StreakOverviewComponent) }
    ]
  },
  {
    path: 'receptionist',
    component: ReceptionistLayoutComponent,
    canActivate: [roleGuard],
    data: { role: 'ROLE_RECEPTIONIST' },
    children: [
      { path: '', redirectTo: 'register', pathMatch: 'full' },
      { path: 'register', loadComponent: () => import('./membership/receptionist/walk-in-registration/walk-in-registration.component').then(m => m.WalkInRegistrationComponent) },
      { path: 'cash-payment', loadComponent: () => import('./payment/receptionist/cash-payment/cash-payment.component').then(m => m.CashPaymentComponent) }
    ]
  },
  {
    path: 'coach',
    component: CoachLayoutComponent,
    canActivate: [roleGuard],
    data: { role: 'ROLE_COACH' },
    children: [
      { path: '', redirectTo: 'schedule', pathMatch: 'full' },
      { path: 'schedule', loadComponent: () => import('./booking/coach/my-classes/my-classes.component').then(m => m.MyClassesComponent) }
    ]
  },
  {
    path: 'member',
    component: MemberLayoutComponent,
    canActivate: [roleGuard],
    data: { role: 'ROLE_MEMBER' },
    children: [
      { path: '', redirectTo: 'subscription', pathMatch: 'full' },
      { path: 'subscription', loadComponent: () => import('./membership/member/my-subscription/my-subscription.component').then(m => m.MySubscriptionComponent) },
      { path: 'plans', loadComponent: () => import('./membership/member/plan-selector/plan-selector.component').then(m => m.PlanSelectorComponent) },
      { path: 'classes', loadComponent: () => import('./booking/member/class-schedule/class-schedule.component').then(m => m.ClassScheduleComponent) },
      { path: 'bookings', loadComponent: () => import('./booking/member/my-bookings/my-bookings.component').then(m => m.MyBookingsComponent) },
      { path: 'checkout', loadComponent: () => import('./payment/member/checkout/checkout.component').then(m => m.CheckoutComponent) },
      { path: 'checkin', loadComponent: () => import('./attendance/member/qr-checkin/qr-checkin.component').then(m => m.QrCheckinComponent) },
      { path: 'schedule', loadComponent: () => import('./attendance/member/weekly-schedule/weekly-schedule.component').then(m => m.WeeklyScheduleComponent) },
      { path: 'streak', loadComponent: () => import('./attendance/member/my-streak/my-streak.component').then(m => m.MyStreakComponent) }
    ]
  },
  {
    path: 'jobs',
    loadComponent: () => import('./hiring/public/job-listings/job-listings.component').then(m => m.JobListingsComponent)
  },
  {
    path: 'jobs/:id/apply',
    loadComponent: () => import('./hiring/public/application-form/application-form.component').then(m => m.ApplicationFormComponent)
  }
];
