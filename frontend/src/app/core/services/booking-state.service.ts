import { Injectable } from '@angular/core';
import { Observable, Subject } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class BookingStateService {
  private readonly bookingCreated$ = new Subject<string>();

  onBookingCreated(hallId: string): void {
    this.bookingCreated$.next(hallId);
  }

  get bookingCreated(): Observable<string> {
    return this.bookingCreated$.asObservable();
  }
}
