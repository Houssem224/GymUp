import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../../auth/auth.service';

export const roleGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  const expectedRole = route.data['role'] as string;

  if (!authService.isLoggedIn()) {
    router.navigate(['/login']);
    return false;
  }

  if (expectedRole && !authService.hasRole(expectedRole)) {
    router.navigate(['/login']);
    return false;
  }

  return true;
};