import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Booking } from '../models';

export interface CreateBookingRequest {
  hall_id: string;
  start_datetime: string;
  end_datetime: string;
  client_type: 'individual' | 'company';
  client_full_name: string;
  client_email: string;
  client_phone: string;
  client_company_name?: string;
}

@Injectable({ providedIn: 'root' })
export class BookingsService {
  constructor(private http: HttpClient) {}

  createBooking(data: CreateBookingRequest): Observable<Booking> {
    return this.http.post<Booking>('/api/v1/bookings', data);
  }
}
