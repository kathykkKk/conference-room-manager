import { Component, inject, DestroyRef } from '@angular/core';
import { CommonModule, CurrencyPipe, DecimalPipe } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';

import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatTableModule } from '@angular/material/table';
import { MatDividerModule } from '@angular/material/divider';

import { ManagerService, IncomeReportRow } from '../../../core/services/manager.service';
import { NotificationService } from '../../../core/services/notification.service';
import { LoadingSpinnerComponent } from '../../../shared/components/loading-spinner/loading-spinner.component';
import { ErrorMessageComponent } from '../../../shared/components/error-message/error-message.component';

interface ReportTableRow extends Partial<IncomeReportRow> {
  isTotals?: boolean;
  period: string;
  hall: string;
  business_park: string;
  bookings_count: number;
  total_bookings_amount: number;
  paid_amount: number;
  refunds_amount: number;
  net_income: number;
  paid_percentage: number;
  average_check: number;
}

@Component({
  selector: 'app-manager-reports',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    CurrencyPipe,
    DecimalPipe,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatTableModule,
    MatDividerModule,
    LoadingSpinnerComponent,
    ErrorMessageComponent
  ],
  templateUrl: './manager-reports.component.html',
  styleUrls: ['./manager-reports.component.scss']
})
export class ManagerReportsComponent {
  private readonly managerService = inject(ManagerService);
  private readonly notificationService = inject(NotificationService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  reportData: IncomeReportRow[] = [];
  tableData: ReportTableRow[] = [];
  totals: Partial<ReportTableRow> = {};
  isLoading = false;
  error: string | null = null;
  reportGenerated = false;

  readonly displayedColumns = [
    'period', 'hall', 'business_park', 'bookings_count',
    'total_bookings_amount', 'paid_amount', 'refunds_amount',
    'net_income', 'paid_percentage', 'average_check'
  ];

  filterForm = this.fb.group({
    date_from: [null as Date | null, Validators.required],
    date_to: [null as Date | null, Validators.required]
  });

  generateReport(): void {
    if (this.filterForm.invalid) {
      this.filterForm.markAllAsTouched();
      this.notificationService.warning('Выберите период для формирования отчёта');
      return;
    }

    const { date_from, date_to } = this.filterForm.value;
    const startDate = date_from as Date;
    const endDate = date_to as Date;

    if (endDate < startDate) {
      this.notificationService.warning('Дата окончания должна быть позже даты начала');
      return;
    }

    this.isLoading = true;
    this.error = null;

    const formatDate = (d: Date): string => d.toISOString().split('T')[0];

    this.managerService.getIncomeReport({
      date_from: formatDate(startDate),
      date_to: formatDate(endDate)
    })
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isLoading = false),
        catchError(err => {
          this.error = err?.error?.message || 'Ошибка формирования отчёта';
          return of([]);
        })
      )
      .subscribe(data => {
        this.reportData = data;
        this.reportGenerated = true;
        this.buildTableData();
        if (data.length === 0) {
          this.notificationService.warning('За выбранный период данных нет');
        }
      });
  }

  private buildTableData(): void {
    const totalRow: ReportTableRow = {
      isTotals: true,
      period: 'ИТОГО',
      hall: '',
      business_park: '',
      bookings_count: this.reportData.reduce((s, r) => s + r.bookings_count, 0),
      total_bookings_amount: this.reportData.reduce((s, r) => s + r.total_bookings_amount, 0),
      paid_amount: this.reportData.reduce((s, r) => s + r.paid_amount, 0),
      refunds_amount: this.reportData.reduce((s, r) => s + r.refunds_amount, 0),
      net_income: this.reportData.reduce((s, r) => s + r.net_income, 0),
      paid_percentage: 0,
      average_check: 0
    };

    this.totals = totalRow;
    this.tableData = [
      ...this.reportData.map(r => ({ ...r, isTotals: false } as ReportTableRow)),
      totalRow
    ];
  }
}
