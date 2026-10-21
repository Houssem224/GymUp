import { Routes } from '@angular/router';
import { JobListingsComponent } from './public/job-listings/job-listings.component';
import { ApplicationFormComponent } from './public/application-form/application-form.component';
import { PostingListComponent } from './admin/posting-list/posting-list.component';
import { ApplicationReviewComponent } from './admin/application-review/application-review.component';

export const hiringRoutes: Routes = [
  {
    path: '',
    redirectTo: 'jobs',
    pathMatch: 'full',
  },
  {
    path: 'jobs',
    component: JobListingsComponent,
    title: 'Careers & Opportunities | GymUp',
  },
  {
    path: 'apply',
    component: ApplicationFormComponent,
    title: 'Apply For Position | GymUp',
  },
  {
    path: 'admin/postings',
    component: PostingListComponent,
    title: 'Manage Job Postings | GymUp',
  },
  {
    path: 'admin/applications',
    component: ApplicationReviewComponent,
    title: 'Review Applications | GymUp',
  },
];
