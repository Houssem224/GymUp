export type PositionType = 'COACH' | 'RECEPTIONIST';
export type JobPostingStatus = 'OPEN' | 'CLOSED';
export type ApplicationStatus = 'PENDING' | 'ACCEPTED' | 'REJECTED';

export interface JobPosting {
  id: number;
  positionType: PositionType;
  status: JobPostingStatus;
  openedAt: string;
  applicationCount?: number;
  title?: string;
  description?: string;
  perks?: string[];
}

export interface Application {
  id: number;
  jobPostingId?: number;
  positionType: PositionType;
  fullName: string;
  email: string;
  cvPath: string;
  status: ApplicationStatus;
  submittedAt: string;
}

export interface ApplyJobRequest {
  fullName: string;
  email: string;
  cvPath?: string;
  cvFile?: File;
}

export interface ApplyJobResponse {
  message: string;
  application: {
    id: number;
    fullName: string;
    email: string;
    position: PositionType;
    status: ApplicationStatus;
    submittedAt: string;
  };
}

export interface AcceptApplicationResponse {
  message: string;
  applicationStatus: ApplicationStatus;
  staffAccount: {
    id: number;
    email: string;
    fullName: string;
    role: string;
  };
}

export interface RejectApplicationResponse {
  message: string;
  applicationStatus: ApplicationStatus;
}
