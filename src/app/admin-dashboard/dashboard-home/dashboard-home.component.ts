import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardApiService, DashboardMetrics } from '../dashboard-api.service';

@Component({
  selector: 'app-dashboard-home',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard-home.component.html',
  styleUrl: './dashboard-home.component.scss'
})
export class DashboardHomeComponent implements OnInit {
  metrics: DashboardMetrics | null = null;
  loading = true;
  error = '';

  constructor(private dashboardApi: DashboardApiService) {}

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard(): void {
    this.loading = true;
    this.dashboardApi.getDashboardMetrics().subscribe({
      next: (data) => {
        this.metrics = data;
        this.loading = false;
      },
      error: (err) => {
        this.error = 'Failed to load dashboard data';
        this.loading = false;
      }
    });
  }
}