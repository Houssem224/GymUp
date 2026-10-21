import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../auth/auth.service';

@Component({
  selector: 'app-coach-layout',
  templateUrl: './coach-layout.component.html',
  styleUrl: './coach-layout.component.scss'
})
export class CoachLayoutComponent {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }

  get userName(): string {
    return this.authService.getCurrentUser()?.fullName || 'Coach';
  }
}