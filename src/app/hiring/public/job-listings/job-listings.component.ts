import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { HiringApiService } from '../../hiring-api.service';
import { JobPosting, PositionType } from '../../shared/application.model';

@Component({
  selector: 'app-job-listings',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './job-listings.component.html',
  styleUrl: './job-listings.component.scss',
})
export class JobListingsComponent implements OnInit {
  private hiringService = inject(HiringApiService);
  private router = inject(Router);

  jobs: JobPosting[] = [];
  selectedFilter: 'ALL' | PositionType = 'ALL';
  isLoading = true;

  get filteredJobs(): JobPosting[] {
    if (this.selectedFilter === 'ALL') return this.jobs;
    return this.jobs.filter((j) => j.positionType === this.selectedFilter);
  }

  ngOnInit(): void {
    this.loadJobs();
  }

  loadJobs(): void {
    this.isLoading = true;
    this.hiringService.getOpenJobs().subscribe((data) => {
      this.jobs = data;
      this.isLoading = false;
    });
  }

  applyFor(job: JobPosting): void {
    this.router.navigate(['/hiring/apply'], {
      queryParams: { postingId: job.id, position: job.positionType },
    });
  }
}
