import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { MembershipApiService, Plan } from '../../membership-api.service';

@Component({
  selector: 'app-plan-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './plan-list.component.html',
  styleUrl: './plan-list.component.scss'
})
export class PlanListComponent implements OnInit {
  plans: Plan[] = [];
  loading = true;
  error = '';

  constructor(
    private membershipApi: MembershipApiService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadPlans();
  }

  loadPlans(): void {
    this.loading = true;
    this.membershipApi.getPlans().subscribe({
      next: (data) => {
        this.plans = data;
        this.loading = false;
      },
      error: (err) => {
        this.error = 'Failed to load plans';
        this.loading = false;
      }
    });
  }

  createPlan(): void {
    this.router.navigate(['/admin/plans/new']);
  }

  editPlan(id: number): void {
    this.router.navigate(['/admin/plans', id, 'edit']);
  }

  activatePlan(id: number): void {
    this.membershipApi.activatePlan(id).subscribe({
      next: () => {
        this.loadPlans();
      },
      error: (err) => {
        this.error = 'Failed to activate plan';
      }
    });
  }

  deactivatePlan(id: number): void {
    this.membershipApi.deactivatePlan(id).subscribe({
      next: () => {
        this.loadPlans();
      },
      error: (err) => {
        this.error = 'Failed to deactivate plan';
      }
    });
  }

  getAccessScopeLabel(scope: string): string {
    const labels: { [key: string]: string } = {
      'OPEN_GYM': 'Open Gym Only',
      'CLASSES': 'Classes Only',
      'BOTH': 'Open Gym + Classes'
    };
    return labels[scope] || scope;
  }
}