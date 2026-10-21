import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../auth/auth.service';

@Component({
  selector: 'app-member-layout',
  templateUrl: './member-layout.component.html',
  styleUrl: './member-layout.component.scss'
})
export class MemberLayoutComponent {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }

  get userName(): string {
    return this.authService.getCurrentUser()?.fullName || 'Member';
  }
}