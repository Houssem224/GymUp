import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HiringApiService } from '../../hiring-api.service';
import { Application, ApplicationStatus } from '../../shared/application.model';

@Component({
  selector: 'app-application-review',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './application-review.component.html',
  styleUrl: './application-review.component.scss',
})
export class ApplicationReviewComponent implements OnInit {
  private hiringService = inject(HiringApiService);
  private route = inject(ActivatedRoute);

  applications: Application[] = [];
  selectedStatus: 'ALL' | ApplicationStatus = 'ALL';
  searchTerm = '';
  isLoading = true;

  actionSuccessMessage: string | null = null;
  processingAppId: number | null = null;

  get filteredApplications(): Application[] {
    return this.applications.filter((app) => {
      const matchesStatus =
        this.selectedStatus === 'ALL' || app.status === this.selectedStatus;
      const query = this.searchTerm.toLowerCase();
      const matchesSearch =
        !query ||
        app.fullName.toLowerCase().includes(query) ||
        app.email.toLowerCase().includes(query);
      return matchesStatus && matchesSearch;
    });
  }

  get pendingCount(): number {
    return this.applications.filter((a) => a.status === 'PENDING').length;
  }

  get acceptedCount(): number {
    return this.applications.filter((a) => a.status === 'ACCEPTED').length;
  }

  get rejectedCount(): number {
    return this.applications.filter((a) => a.status === 'REJECTED').length;
  }

  ngOnInit(): void {
    this.route.queryParams.subscribe((params) => {
      if (params['status']) {
        this.selectedStatus = params['status'];
      }
      this.loadApplications();
    });
  }

  loadApplications(): void {
    this.isLoading = true;
    this.hiringService.getApplications().subscribe((data) => {
      this.applications = data;
      this.isLoading = false;
    });
  }

  accept(app: Application): void {
    if (
      !confirm(
        `Accept application for ${app.fullName}? This will automatically generate a staff account and email login credentials.`
      )
    ) {
      return;
    }

    this.processingAppId = app.id;
    this.hiringService.acceptApplication(app.id).subscribe({
      next: (res) => {
        app.status = 'ACCEPTED';
        this.processingAppId = null;
        this.actionSuccessMessage = `✓ ${app.fullName} accepted! Staff account created: ${res.staffAccount?.email || app.email} (${res.staffAccount?.role || 'Staff'})`;
        setTimeout(() => (this.actionSuccessMessage = null), 6000);
      },
      error: () => {
        this.processingAppId = null;
      },
    });
  }

  reject(app: Application): void {
    if (!confirm(`Reject candidate ${app.fullName}? An automated update email will be sent.`)) {
      return;
    }

    this.processingAppId = app.id;
    this.hiringService.rejectApplication(app.id).subscribe({
      next: (res) => {
        app.status = 'REJECTED';
        this.processingAppId = null;
        this.actionSuccessMessage = `Candidate ${app.fullName} marked as REJECTED. Notification sent.`;
        setTimeout(() => (this.actionSuccessMessage = null), 5000);
      },
      error: () => {
        this.processingAppId = null;
      },
    });
  }
}
