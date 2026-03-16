import { Component, OnInit, inject, DestroyRef } from '@angular/core';
import { CommonModule, DatePipe, CurrencyPipe } from '@angular/common';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatChipsModule } from '@angular/material/chips';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatDialogModule, MatDialog } from '@angular/material/dialog';
import { MatSortModule } from '@angular/material/sort';

import { ManagerService } from '../../../core/services/manager.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Booking } from '../../../core/models';
import { LoadingSpinnerComponent } from '../../../shared/components/loading-spinner/loading-spinner.component';
import { ErrorMessageComponent } from '../../../shared/components/error-message/error-message.component';

@Component({
  selector: 'app-manager-bookings',
  standalone: true,
  imports: [
    CommonModule,
    DatePipe,
    CurrencyPipe,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatChipsModule,
    MatTooltipModule,
    MatDialogModule,
    MatSortModule,
    LoadingSpinnerComponent,
    ErrorMessageComponent
  ],
  templateUrl: './manager-bookings.component.html',
  styleUrls: ['./manager-bookings.component.scss']
})
export class ManagerBookingsComponent implements OnInit {
  private readonly managerService = inject(ManagerService);
  private readonly notificationService = inject(NotificationService);
  private readonly destroyRef = inject(DestroyRef);

  bookings: Booking[] = [];
  isLoading = false;
  error: string | null = null;
  actionInProgress: string | null = null;

  readonly displayedColumns = ['hall', 'client', 'start_datetime', 'end_datetime', 'total_price', 'status', 'actions'];

  readonly statusLabels: Record<string, string> = {
    pending: 'Ожидает',
    confirmed: 'Подтверждено',
    cancelled: 'Отменено',
    completed: 'Завершено'
  };

  ngOnInit(): void {
    this.loadBookings();
  }

  loadBookings(): void {
    this.isLoading = true;
    this.error = null;
    this.managerService.getBookings()
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isLoading = false),
        catchError(() => {
          this.error = 'Ошибка загрузки бронирований';
          return of([]);
        })
      )
      .subscribe(bookings => {
        this.bookings = bookings;
      });
  }

  confirmBooking(booking: Booking): void {
    if (this.actionInProgress) return;
    this.actionInProgress = booking.id;

    this.managerService.confirmBooking(booking.id)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.actionInProgress = null),
        catchError(err => {
          this.notificationService.error(err?.error?.message || 'Ошибка подтверждения бронирования');
          return of(null);
        })
      )
      .subscribe(updated => {
        if (updated) {
          this.notificationService.success('Бронирование подтверждено');
          this.loadBookings();
        }
      });
  }

  cancelBooking(booking: Booking): void {
    if (this.actionInProgress) return;
    this.actionInProgress = booking.id;

    this.managerService.cancelBooking(booking.id)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.actionInProgress = null),
        catchError(err => {
          this.notificationService.error(err?.error?.message || 'Ошибка отмены бронирования');
          return of(null);
        })
      )
      .subscribe(updated => {
        if (updated) {
          this.notificationService.success('Бронирование отменено');
          this.loadBookings();
        }
      });
  }

  canConfirm(booking: Booking): boolean {
    return booking.status === 'pending';
  }

  canCancel(booking: Booking): boolean {
    return booking.status === 'pending' || booking.status === 'confirmed';
  }

  getStatusLabel(status: string): string {
    return this.statusLabels[status] || status;
  }
}
