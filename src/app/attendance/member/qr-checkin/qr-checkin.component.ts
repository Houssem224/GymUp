import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AttendanceApiService } from '../../attendance-api.service';
import { CheckInResult } from '../../shared/streak.model';

@Component({
  selector: 'app-qr-checkin',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './qr-checkin.component.html',
  styleUrl: './qr-checkin.component.scss',
})
export class QrCheckinComponent {
  private attendanceService = inject(AttendanceApiService);

  isScanning = false;
  scanResult: CheckInResult | null = null;
  errorMessage: string | null = null;

  triggerScan(): void {
    this.isScanning = true;
    this.errorMessage = null;

    // Simulate scanning laser for 1.2s then send check-in request
    setTimeout(() => {
      this.attendanceService.processCheckIn().subscribe({
        next: (res) => {
          this.isScanning = false;
          this.scanResult = res;
        },
        error: (err) => {
          this.isScanning = false;
          this.errorMessage =
            err?.error?.error || 'Unable to complete check-in. Please show your barcode to staff.';
        },
      });
    }, 1200);
  }

  dismissResult(): void {
    this.scanResult = null;
  }
}
