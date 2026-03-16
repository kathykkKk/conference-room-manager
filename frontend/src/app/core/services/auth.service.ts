import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { Router } from '@angular/router';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly TOKEN_KEY = 'manager_token';
  readonly isAuthenticated = signal(this.hasToken());

  constructor(private http: HttpClient, private router: Router) {}

  login(email: string, password: string): Observable<{ token: string }> {
    return this.http.post<{ token: string }>('/api/v1/login', { email, password }).pipe(
      tap(res => {
        localStorage.setItem(this.TOKEN_KEY, res.token);
        this.isAuthenticated.set(true);
      })
    );
  }

  logout(redirectTo = '/manager/login'): void {
    this.http.post('/api/v1/manager/logout', {}).subscribe({
      complete: () => this.clearSession(redirectTo),
      error: () => this.clearSession(redirectTo)
    });
  }

  clearSession(redirectTo = '/manager/login'): void {
    localStorage.removeItem(this.TOKEN_KEY);
    this.isAuthenticated.set(false);
    this.router.navigate([redirectTo]);
  }

  getToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  private hasToken(): boolean {
    return !!localStorage.getItem(this.TOKEN_KEY);
  }
}
