import { Component, OnInit, inject, DestroyRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatDividerModule } from '@angular/material/divider';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';

import { ManagerService, CreateHallRequest } from '../../../core/services/manager.service';
import { HallsService } from '../../../core/services/halls.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Hall, BusinessPark } from '../../../core/models';
import { LoadingSpinnerComponent } from '../../../shared/components/loading-spinner/loading-spinner.component';
import { ErrorMessageComponent } from '../../../shared/components/error-message/error-message.component';

@Component({
  selector: 'app-hall-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterLink,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatDividerModule,
    MatProgressSpinnerModule,
    LoadingSpinnerComponent,
    ErrorMessageComponent
  ],
  templateUrl: './hall-form.component.html',
  styleUrls: ['./hall-form.component.scss']
})
export class HallFormComponent implements OnInit {
  private readonly managerService = inject(ManagerService);
  private readonly hallsService = inject(HallsService);
  private readonly notificationService = inject(NotificationService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  isLoading = false;
  isSubmitting = false;
  error: string | null = null;
  isEditMode = false;
  hallId: string | null = null;
  businessParks: BusinessPark[] = [];

  hallForm = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(255)]],
    business_park_id: [''],
    capacity: [null as number | null, [Validators.required, Validators.min(1), Validators.max(10000)]],
    area_sq_m: [null as number | null, [Validators.required, Validators.min(1)]],
    price_per_hour: [null as number | null, [Validators.min(0)]],
    description: ['', Validators.maxLength(5000)],
    status: ['available' as 'available' | 'unavailable', Validators.required]
  });

  ngOnInit(): void {
    this.loadBusinessParks();

    this.route.params
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(params => {
        if (params['id']) {
          this.isEditMode = true;
          this.hallId = params['id'];
          this.loadHall(this.hallId!);
        }
      });
  }

  private loadBusinessParks(): void {
    this.hallsService.getFilters()
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        catchError(() => of({ business_parks: [], equipment: [] }))
      )
      .subscribe(data => {
        this.businessParks = data.business_parks;
      });
  }

  private loadHall(id: string): void {
    this.isLoading = true;
    this.error = null;

    this.managerService.getHalls()
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isLoading = false),
        catchError(() => {
          this.error = 'Ошибка загрузки данных зала';
          return of([]);
        })
      )
      .subscribe((halls: Hall[]) => {
        const hall = halls.find(h => h.id === id);
        if (hall) {
          this.hallForm.patchValue({
            name: hall.name,
            business_park_id: hall.business_park_id,
            capacity: hall.capacity,
            area_sq_m: hall.area_sq_m,
            price_per_hour: hall.price_per_hour ?? null,
            description: hall.description,
            status: hall.status
          });
        } else {
          this.error = 'Зал не найден';
        }
      });
  }

  get pageTitle(): string {
    return this.isEditMode ? 'Редактирование зала' : 'Создание зала';
  }

  onSubmit(): void {
    if (this.hallForm.invalid) {
      this.hallForm.markAllAsTouched();
      this.notificationService.warning('Заполните все обязательные поля');
      return;
    }

    this.isSubmitting = true;
    const formValue = this.hallForm.value;

    const request: CreateHallRequest = {
      name: formValue.name!,
      capacity: Number(formValue.capacity),
      area_sq_m: Number(formValue.area_sq_m),
      description: formValue.description || '',
      status: formValue.status as 'available' | 'unavailable',
    };

    if (formValue.business_park_id) {
      request.business_park_id = formValue.business_park_id;
    }
    if (formValue.price_per_hour != null) {
      request.price_per_hour = Number(formValue.price_per_hour);
    }

    const operation = this.isEditMode
      ? this.managerService.updateHall(this.hallId!, request)
      : this.managerService.createHall(request);

    operation
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isSubmitting = false),
        catchError(err => {
          const message = err?.error?.message || 'Ошибка сохранения зала';
          this.notificationService.error(message);
          return of(null);
        })
      )
      .subscribe(hall => {
        if (hall) {
          const msg = this.isEditMode ? 'Зал успешно обновлён' : 'Зал успешно создан';
          this.notificationService.success(msg);
          this.router.navigate(['/manager/halls']);
        }
      });
  }
}
