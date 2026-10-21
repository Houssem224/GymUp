import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { User, LoginResponse, LoginRequest, RegisterRequest, UpdateProfileRequest } from '../shared/models/user.model';

@Injectable({
  providedIn: 'root'
})
export class AuthApiService {
  private apiUrl = 'http://localhost:8000/api/auth';

  constructor(private http: HttpClient) {}

  register(data: RegisterRequest): Observable<User> {
    return this.http.post<User>(`${this.apiUrl}/register`, data);
  }

  login(data: LoginRequest): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}/login`, data);
  }

  getCurrentUser(): Observable<User> {
    return this.http.get<User>(`${this.apiUrl}/me`);
  }

  updateProfile(data: UpdateProfileRequest): Observable<User> {
    return this.http.put<User>(`${this.apiUrl}/profile`, data);
  }
}