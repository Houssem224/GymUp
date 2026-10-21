export interface User {
  id: number;
  email: string;
  fullName: string;
  role: 'ROLE_ADMIN' | 'ROLE_RECEPTIONIST' | 'ROLE_COACH' | 'ROLE_MEMBER';
  status: 'ACTIVE' | 'INACTIVE';
}

export interface LoginResponse {
  user: User;
  token: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  fullName: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface UpdateProfileRequest {
  fullName?: string;
  password?: string;
}