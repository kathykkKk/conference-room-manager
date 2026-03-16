import { Component, OnInit, inject, DestroyRef } from '@angular/core';
import { CommonModule, CurrencyPipe, DatePipe } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize, catchError, switchMap } from 'rxjs/operators';
import { of } from 'rxjs';

import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatDividerModule } from '@angular/material/divider';
import { MatTableModule } from '@angular/material/table';
import { MatChipsModule } from '@angular/material/chips';

import { HallsService } from '../../../core/services/halls.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Hall } from '../../../core/models';
import { LoadingSpinnerComponent } from '../../../shared/components/loading-spinner/loading-spinner.component';
import { ErrorMessageComponent } from '../../../shared/components/error-message/error-message.component';

@Component({
  selector: 'app-hall-detail',
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
    MatDatepickerModule,
    MatNativeDateModule,
    MatDividerModule,
    MatTableModule,
    MatChipsModule,
    LoadingSpinnerComponent,
    ErrorMessageComponent
  ],
  templateUrl: './hall-detail.component.html',
  styleUrls: ['./hall-detail.component.scss']
})
export class HallDetailComponent implements OnInit {
  private readonly hallsService = inject(HallsService);
  private readonly notificationService = inject(NotificationService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  hall: Hall | null = null;
  isLoading = false;
  error: string | null = null;
  currentPhotoIndex = 0;
  selectedStartDatetime: string | null = null;
  selectedEndDatetime: string | null = null;
  calculatedPrice: number | null = null;
  minDate = new Date();

  readonly timeOptions = [
    '08:00', '09:00', '10:00', '11:00', '12:00', '13:00',
    '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
    '20:00', '21:00', '22:00'
  ];

  bookingForm: FormGroup = this.fb.group({
    date: [null, Validators.required],
    start_time: ['', Validators.required],
    end_time: ['', Validators.required]
  });

  readonly equipmentColumns = ['name', 'category', 'quantity'];

  ngOnInit(): void {
    this.route.params
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        switchMap(params => {
          this.isLoading = true;
          this.error = null;
          return this.hallsService.getHall(params['id']).pipe(
            catchError(err => {
              this.error = 'Ошибка загрузки зала. Попробуйте ещё раз.';
              return of(null);
            }),
            finalize(() => this.isLoading = false)
          );
        })
      )
      .subscribe(hall => {
        this.hall = hall;
      });

    this.bookingForm.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.calculatePrice());
  }

  calculatePrice(): void {
    const { date, start_time, end_time } = this.bookingForm.value;
    const priceRaw = this.hall?.current_price ?? this.hall?.price_per_hour
      ?? (this.hall?.pricing_rules?.length ? Number(this.hall.pricing_rules[0].price_per_hour) : null);
    if (!date || !start_time || !end_time || !priceRaw) {
      this.calculatedPrice = null;
      return;
    }

    const startDate = new Date(date);
    const [startH, startM] = start_time.split(':');
    startDate.setHours(Number(startH), Number(startM), 0, 0);

    const endDate = new Date(date);
    const [endH, endM] = end_time.split(':');
    endDate.setHours(Number(endH), Number(endM), 0, 0);

    if (endDate <= startDate) {
      this.calculatedPrice = null;
      return;
    }

    const hours = (endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60);
    this.calculatedPrice = hours * Number(priceRaw);

    this.selectedStartDatetime = startDate.toISOString();
    this.selectedEndDatetime = endDate.toISOString();
  }

  onBook(): void {
    if (this.bookingForm.invalid) {
      this.notificationService.warning('Выберите дату и время бронирования');
      return;
    }

    this.calculatePrice();

    if (!this.selectedStartDatetime || !this.selectedEndDatetime) {
      this.notificationService.warning('Время окончания должно быть позже времени начала');
      return;
    }

    this.router.navigate(['/booking/new'], {
      queryParams: {
        hallId: this.hall!.id,
        start: this.selectedStartDatetime,
        end: this.selectedEndDatetime
      }
    });
  }

  prevPhoto(): void {
    if (this.hall?.photos && this.currentPhotoIndex > 0) {
      this.currentPhotoIndex--;
    }
  }

  nextPhoto(): void {
    if (this.hall?.photos && this.currentPhotoIndex < this.hall.photos.length - 1) {
      this.currentPhotoIndex++;
    }
  }

  getCurrentPhoto(): string {
    return this.hall?.photos?.[this.currentPhotoIndex]?.url || '';
  }

  hasPhotos(): boolean {
    return !!(this.hall?.photos && this.hall.photos.length > 0);
  }

  getStatusLabel(status: string): string {
    return status === 'available' ? 'Доступен' : 'Недоступен';
  }
}
