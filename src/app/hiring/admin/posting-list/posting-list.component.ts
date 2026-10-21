import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { HiringApiService } from '../../hiring-api.service';
import { JobPosting, PositionType } from '../../shared/application.model';

@Component({
  selector: 'app-posting-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './posting-list.component.html',
  styleUrl: './posting-list.component.scss',
})
export class PostingListComponent implements OnInit {
  private hiringService = inject(HiringApiService);
  private router = inject(Router);

  postings: JobPosting[] = [];
  isLoading = true;

  // New posting modal
  showCreateModal = false;
  newPositionType: PositionType = 'COACH';
  isCreating = false;
  feedbackMessage: string | null = null;

  ngOnInit(): void {
    this.loadPostings();
  }

  loadPostings(): void {
    this.isLoading = true;
    this.hiringService.getAdminJobPostings().subscribe((data) => {
      this.postings = data;
      this.isLoading = false;
    });
  }

  openCreateModal(): void {
    this.showCreateModal = true;
    this.newPositionType = 'COACH';
    this.feedbackMessage = null;
  }

  closeCreateModal(): void {
    this.showCreateModal = false;
  }

  submitCreatePosting(): void {
    this.isCreating = true;
    this.hiringService.createJobPosting(this.newPositionType).subscribe({
      next: (res) => {
        this.isCreating = false;
        this.showCreateModal = false;
        this.loadPostings();
        this.feedbackMessage = `Successfully opened new ${this.newPositionType} job posting!`;
        setTimeout(() => (this.feedbackMessage = null), 4000);
      },
      error: () => {
        this.isCreating = false;
      },
    });
  }

  closePosting(posting: JobPosting): void {
    if (confirm(`Are you sure you want to close posting #${posting.id} (${posting.positionType})?`)) {
      this.hiringService.closeJobPosting(posting.id).subscribe(() => {
        posting.status = 'CLOSED';
        this.feedbackMessage = `Posting #${posting.id} closed.`;
        setTimeout(() => (this.feedbackMessage = null), 3000);
      });
    }
  }

  goToApplications(posting: JobPosting): void {
    this.router.navigate(['/hiring/admin/applications'], {
      queryParams: { postingId: posting.id },
    });
  }
}
