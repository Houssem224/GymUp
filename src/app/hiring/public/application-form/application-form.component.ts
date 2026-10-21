import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HiringApiService } from '../../hiring-api.service';
import { ApplyJobResponse, JobPosting } from '../../shared/application.model';

@Component({
  selector: 'app-application-form',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './application-form.component.html',
  styleUrl: './application-form.component.scss',
})
export class ApplicationFormComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private hiringService = inject(HiringApiService);

  availablePostings: JobPosting[] = [];
  selectedPostingId: number = 1;

  fullName = '';
  email = '';
  phone = '';
  cvPath = '';
  selectedFileName = '';
  coverNote = '';
  agreeTerms = false;

  isSubmitting = false;
  errorMessage: string | null = null;
  applicationSuccess: ApplyJobResponse | null = null;

  ngOnInit(): void {
    this.hiringService.getOpenJobs().subscribe((jobs) => {
      this.availablePostings = jobs;
      if (jobs.length > 0) {
        this.selectedPostingId = jobs[0].id;
      }

      this.route.queryParams.subscribe((params) => {
        if (params['postingId']) {
          this.selectedPostingId = Number(params['postingId']);
        }
      });
    });
  }

  onFileSelected(event: any): void {
    const file = event.target.files?.[0];
    if (file) {
      this.selectedFileName = file.name;
      this.cvPath = `/uploads/cvs/${file.name}`;
    }
  }

  submitApplication(): void {
    if (!this.fullName || !this.email) {
      this.errorMessage = 'Please provide both your full name and valid email.';
      return;
    }

    if (!this.cvPath && !this.selectedFileName) {
      this.cvPath = 'https://linkedin.com/in/' + this.fullName.toLowerCase().replace(/\s+/g, '-');
    }

    this.isSubmitting = true;
    this.errorMessage = null;

    this.hiringService
      .submitApplication(this.selectedPostingId, {
        fullName: this.fullName,
        email: this.email,
        cvPath: this.cvPath,
      })
      .subscribe({
        next: (res) => {
          this.isSubmitting = false;
          this.applicationSuccess = res;
        },
        error: (err) => {
          this.isSubmitting = false;
          this.errorMessage =
            err?.error?.error || 'Failed to submit application. Please check your information.';
        },
      });
  }

  resetForm(): void {
    this.applicationSuccess = null;
    this.fullName = '';
    this.email = '';
    this.phone = '';
    this.cvPath = '';
    this.selectedFileName = '';
    this.coverNote = '';
    this.agreeTerms = false;
  }
}
