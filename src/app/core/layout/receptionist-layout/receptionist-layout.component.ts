import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../auth/auth.service';

@Component({
  selector: 'app-receptionist-layout',
  templateUrl: './receptionist-layout.component.html',
  styleUrl: './receptionist-layout.component.scss'
})
export class ReceptionistLayoutComponent {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }

  get userName(): string {
    return this.authService.getCurrentUser()?.fullName || 'Receptionist';
  }
}