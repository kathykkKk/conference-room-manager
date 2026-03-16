import { Component, OnInit, inject, DestroyRef } from '@angular/core';
import { CommonModule, CurrencyPipe } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatChipsModule } from '@angular/material/chips';
import { MatDividerModule } from '@angular/material/divider';
import { MatExpansionModule } from '@angular/material/expansion';

import { HallsService, HallSearchParams } from '../../../core/services/halls.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Hall, BusinessPark, Equipment, HallFiltersData } from '../../../core/models';
import { LoadingSpinnerComponent } from '../../../shared/components/loading-spinner/loading-spinner.component';
import { ErrorMessageComponent } from '../../../shared/components/error-message/error-message.component';

@Component({
  selector: 'app-halls-list',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterLink,
    CurrencyPipe,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatChipsModule,
    MatDividerModule,
    MatExpansionModule,
    LoadingSpinnerComponent,
    ErrorMessageComponent
  ],
  templateUrl: './halls-list.component.html',
  styleUrls: ['./halls-list.component.scss']
})
export class HallsListComponent implements OnInit {
  private readonly hallsService = inject(HallsService);
  private readonly notificationService = inject(NotificationService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  halls: Hall[] = [];
  filtersData: HallFiltersData | null = null;
  isLoading = false;
  isFiltersLoading = false;
  error: string | null = null;

  readonly timeOptions = [
    '08:00', '09:00', '10:00', '11:00', '12:00', '13:00',
    '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
    '20:00', '21:00', '22:00'
  ];

  searchForm: FormGroup = this.fb.group({
    date: [null],
    start_time: [''],
    end_time: [''],
    business_park_id: [''],
    city: [''],
    capacity_from: [null],
    capacity_to: [null],
    min_price: [null],
    max_price: [null],
    equipment: [[]]
  });

  ngOnInit(): void {
    this.loadFilters();
    this.loadHalls({});
  }

  private loadFilters(): void {
    this.isFiltersLoading = true;
    this.hallsService.getFilters()
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isFiltersLoading = false),
        catchError(err => {
          this.notificationService.error('Ошибка загрузки фильтров');
          return of({ business_parks: [], equipment: [] } as HallFiltersData);
        })
      )
      .subscribe(data => {
        this.filtersData = data;
      });
  }

  loadHalls(params: HallSearchParams): void {
    this.isLoading = true;
    this.error = null;
    this.hallsService.searchHalls(params)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isLoading = false),
        catchError(err => {
          this.error = 'Ошибка загрузки залов. Попробуйте ещё раз.';
          return of([]);
        })
      )
      .subscribe(halls => {
        this.halls = halls;
      });
  }

  onSearch(): void {
    const formValue = this.searchForm.value;
    const params: HallSearchParams = {};

    if (formValue.business_park_id) {
      params.business_park_id = formValue.business_park_id;
    }
    if (formValue.city) {
      params.city = formValue.city;
    }
    if (formValue.capacity_from != null && formValue.capacity_from !== '') {
      params.capacity_from = Number(formValue.capacity_from);
    }
    if (formValue.capacity_to != null && formValue.capacity_to !== '') {
      params.capacity_to = Number(formValue.capacity_to);
    }
    if (formValue.min_price != null && formValue.min_price !== '') {
      params.min_price = Number(formValue.min_price);
    }
    if (formValue.max_price != null && formValue.max_price !== '') {
      params.max_price = Number(formValue.max_price);
    }
    if (formValue.equipment?.length) {
      params.equipment_category = formValue.equipment;
    }

    if (formValue.date && formValue.start_time) {
      const date = new Date(formValue.date);
      const [hours, minutes] = formValue.start_time.split(':');
      date.setHours(Number(hours), Number(minutes), 0, 0);
      params.date_range_start = date.toISOString();
    }
    if (formValue.date && formValue.end_time) {
      const date = new Date(formValue.date);
      const [hours, minutes] = formValue.end_time.split(':');
      date.setHours(Number(hours), Number(minutes), 0, 0);
      params.date_range_end = date.toISOString();
    }

    this.loadHalls(params);
  }

  onReset(): void {
    this.searchForm.reset({ equipment: [] });
    this.loadHalls({});
  }

  getHallPhoto(hall: Hall): string {
    return hall.photos?.[0]?.url || '';
  }

  hasPhoto(hall: Hall): boolean {
    return !!(hall.photos && hall.photos.length > 0 && hall.photos[0].url);
  }

  getStatusLabel(status: string): string {
    return status === 'available' ? 'Доступен' : 'Недоступен';
  }
}
