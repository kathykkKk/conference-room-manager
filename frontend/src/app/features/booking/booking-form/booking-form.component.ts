import { Component, OnInit, inject, DestroyRef } from '@angular/core';
import { CommonModule, CurrencyPipe, DatePipe } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatRadioModule } from '@angular/material/radio';
import { MatDividerModule } from '@angular/material/divider';
import { MatProgressBarModule } from '@angular/material/progress-bar';

import { HallsService } from '../../../core/services/halls.service';
import { BookingsService } from '../../../core/services/bookings.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Hall, Booking } from '../../../core/models';
import { LoadingSpinnerComponent } from '../../../shared/components/loading-spinner/loading-spinner.component';
import { ErrorMessageComponent } from '../../../shared/components/error-message/error-message.component';

@Component({
  selector: 'app-booking-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterLink,
    CurrencyPipe,
    DatePipe,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatRadioModule,
    MatDividerModule,
    MatProgressBarModule,
    LoadingSpinnerComponent,
    ErrorMessageComponent
  ],
  templateUrl: './booking-form.component.html',
  styleUrls: ['./booking-form.component.scss']
})
export class BookingFormComponent implements OnInit {
  private readonly hallsService = inject(HallsService);
  private readonly bookingsService = inject(BookingsService);
  private readonly notificationService = inject(NotificationService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  hall: Hall | null = null;
  isLoading = false;
  isSubmitting = false;
  error: string | null = null;

  hallId: string = '';
  startDatetime: string = '';
  endDatetime: string = '';
  calculatedPrice: number | null = null;

  bookingForm: FormGroup = this.fb.group({
    client_type: ['individual', Validators.required],
    full_name: ['', [Validators.required, Validators.minLength(2)]],
    email: ['', [Validators.required, Validators.email]],
    phone: ['', [Validators.required, Validators.pattern(/^\+?[0-9\s\-\(\)]{10,20}$/)]],
    company_name: ['']
  });

  ngOnInit(): void {
    this.route.queryParams
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(params => {
        this.hallId = params['hallId'] || '';
        this.startDatetime = params['start'] || '';
        this.endDatetime = params['end'] || '';

        if (!this.hallId || !this.startDatetime || !this.endDatetime) {
          this.notificationService.error('Некорректные параметры бронирования');
          this.router.navigate(['/']);
          return;
        }

        this.calculatePrice();
        this.loadHall(this.hallId);
      });

    this.bookingForm.get('client_type')?.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(type => {
        const companyCtrl = this.bookingForm.get('company_name');
        if (type === 'company') {
          companyCtrl?.setValidators([Validators.required]);
        } else {
          companyCtrl?.clearValidators();
          companyCtrl?.setValue('');
        }
        companyCtrl?.updateValueAndValidity();
      });
  }

  private loadHall(id: string): void {
    this.isLoading = true;
    this.error = null;
    this.hallsService.getHall(id)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isLoading = false),
        catchError(() => {
          this.error = 'Ошибка загрузки информации о зале';
          return of(null);
        })
      )
      .subscribe(hall => {
        this.hall = hall;
        this.calculatePrice();
      });
  }

  private calculatePrice(): void {
    if (!this.startDatetime || !this.endDatetime || (!this.hall?.current_price && !this.hall?.price_per_hour)) {
      return;
    }
    const start = new Date(this.startDatetime);
    const end = new Date(this.endDatetime);
    const hours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
    const pricePerHour = this.hall.current_price ?? this.hall.price_per_hour ?? 0;
    this.calculatedPrice = hours * pricePerHour;
  }

  get isCompany(): boolean {
    return this.bookingForm.get('client_type')?.value === 'company';
  }

  getDurationHours(): number {
    if (!this.startDatetime || !this.endDatetime) return 0;
    return (new Date(this.endDatetime).getTime() - new Date(this.startDatetime).getTime()) / (1000 * 60 * 60);
  }

  onSubmit(): void {
    if (this.bookingForm.invalid) {
      this.bookingForm.markAllAsTouched();
      this.notificationService.warning('Заполните все обязательные поля');
      return;
    }

    this.isSubmitting = true;
    const formValue = this.bookingForm.value;

    const requestData: any = {
      hall_id: this.hallId,
      start_datetime: this.startDatetime,
      end_datetime: this.endDatetime,
      client_type: formValue.client_type,
      client_full_name: formValue.full_name,
      client_email: formValue.email,
      client_phone: formValue.phone
    };

    if (formValue.client_type === 'company' && formValue.company_name) {
      requestData.client_company_name = formValue.company_name;
    }

    this.bookingsService.createBooking(requestData)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isSubmitting = false),
        catchError(err => {
          const message = err?.error?.message || 'Ошибка создания бронирования';
          this.notificationService.error(message);
          return of(null);
        })
      )
      .subscribe(booking => {
        if (booking) {
          this.notificationService.success('Бронирование успешно создано!');
          this.router.navigate(['/booking/success'], {
            queryParams: { bookingId: booking.id }
          });
        }
      });
  }
}
