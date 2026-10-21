import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthApiService } from '../auth-api.service';
import { AuthService } from '../auth.service';
import { RegisterRequest } from '../../shared/models/user.model';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss'
})
export class RegisterComponent {
  registerData: RegisterRequest = {
    email: '',
    password: '',
    fullName: ''
  };
  confirmPassword = '';
  loading = false;
  error = '';

  constructor(
    private authApi: AuthApiService,
    private authService: AuthService,
    private router: Router
  ) {}

  onSubmit(): void {
    if (this.registerData.password !== this.confirmPassword) {
      this.error = 'Passwords do not match';
      return;
    }

    if (this.registerData.password.length < 6) {
      this.error = 'Password must be at least 6 characters';
      return;
    }

    this.loading = true;
    this.error = '';

    this.authApi.register(this.registerData).subscribe({
      next: (user) => {
        // Auto-login after registration
        this.authApi.login({
          email: this.registerData.email,
          password: this.registerData.password
        }).subscribe({
          next: (response) => {
            this.authService.login(response);
            this.loading = false;
            this.router.navigate(['/member/subscription']);
          },
          error: (err) => {
            this.error = 'Registration successful but login failed. Please login manually.';
            this.loading = false;
            this.router.navigate(['/login']);
          }
        });
      },
      error: (err) => {
        this.error = err.error?.error || 'Registration failed. Please try again.';
        this.loading = false;
      }
    });
  }
}