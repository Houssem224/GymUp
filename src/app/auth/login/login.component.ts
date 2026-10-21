import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthApiService } from '../auth-api.service';
import { AuthService } from '../auth.service';
import { LoginRequest } from '../../shared/models/user.model';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent {
  loginData: LoginRequest = {
    email: '',
    password: ''
  };
  loading = false;
  error = '';

  constructor(
    private authApi: AuthApiService,
    private authService: AuthService,
    private router: Router
  ) {}

  onSubmit(): void {
    this.loading = true;
    this.error = '';

    this.authApi.login(this.loginData).subscribe({
      next: (response) => {
        this.authService.login(response);
        this.loading = false;

        // Redirect based on role
        const role = response.user.role;
        switch (role) {
          case 'ROLE_ADMIN':
            this.router.navigate(['/admin/dashboard']);
            break;
          case 'ROLE_RECEPTIONIST':
            this.router.navigate(['/receptionist/register']);
            break;
          case 'ROLE_COACH':
            this.router.navigate(['/coach/schedule']);
            break;
          case 'ROLE_MEMBER':
            this.router.navigate(['/member/subscription']);
            break;
          default:
            this.router.navigate(['/login']);
        }
      },
      error: (err) => {
        this.error = err.error?.error || 'Login failed. Please try again.';
        this.loading = false;
      }
    });
  }
}