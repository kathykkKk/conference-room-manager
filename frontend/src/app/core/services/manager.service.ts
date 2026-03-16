import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { Booking, Hall } from '../models';

export interface CreateHallRequest {
  name: string;
  capacity: number;
  area_sq_m: number;
  description: string;
  status: 'available' | 'unavailable';
  business_park_id?: string;
  price_per_hour?: number;
}

export interface IncomeReportParams {
  date_from: string;
  date_to: string;
}

export interface IncomeReportRow {
  period: string;
  business_park: string;
  hall: string;
  bookings_count: number;
  total_bookings_amount: number;
  paid_amount: number;
  refunds_amount: number;
  net_income: number;
  paid_percentage: number;
  average_check: number;
}

@Injectable({ providedIn: 'root' })
export class ManagerService {
  constructor(private http: HttpClient) {}

  getBookings(): Observable<Booking[]> {
    return this.http.get<{ data: Booking[] }>('/api/v1/manager/bookings').pipe(
      map(response => response.data)
    );
  }

  confirmBooking(id: string): Observable<Booking> {
    return this.http.post<Booking>(`/api/v1/manager/bookings/${id}/confirm`, {});
  }

  cancelBooking(id: string): Observable<Booking> {
    return this.http.post<Booking>(`/api/v1/manager/bookings/${id}/cancel`, {});
  }

  getHalls(): Observable<Hall[]> {
    return this.http.get<Hall[]>('/api/v1/manager/halls');
  }

  createHall(data: CreateHallRequest): Observable<Hall> {
    return this.http.post<Hall>('/api/v1/manager/halls', data);
  }

  updateHall(id: string, data: Partial<CreateHallRequest>): Observable<Hall> {
    return this.http.put<Hall>(`/api/v1/manager/halls/${id}`, data);
  }

  deactivateHall(id: string): Observable<void> {
    return this.http.delete<void>(`/api/v1/manager/halls/${id}`);
  }

  getIncomeReport(params: IncomeReportParams): Observable<IncomeReportRow[]> {
    const httpParams = new HttpParams()
      .set('date_from', params.date_from)
      .set('date_to', params.date_to);
    return this.http.get<{ data: IncomeReportRow[] }>('/api/v1/manager/reports/income', { params: httpParams }).pipe(
      map(response => response.data)
    );
  }
}
