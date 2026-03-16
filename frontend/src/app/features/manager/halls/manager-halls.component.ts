import { Component, OnInit, inject, DestroyRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize, catchError, switchMap } from 'rxjs/operators';
import { of } from 'rxjs';

import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { Component as NgComponent, Inject } from '@angular/core';

@NgComponent({
  selector: 'app-confirm-dialog',
  standalone: true,
  imports: [MatButtonModule, MatDialogModule],
  template: `
    <h2 mat-dialog-title>Подтверждение</h2>
    <mat-dialog-content>{{ data.message }}</mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button [mat-dialog-close]="false">Отмена</button>
      <button mat-raised-button color="warn" [mat-dialog-close]="true">Удалить</button>
    </mat-dialog-actions>
  `
})
export class ConfirmDialogComponent {
  constructor(
    public dialogRef: MatDialogRef<ConfirmDialogComponent>,
    @Inject(MAT_DIALOG_DATA) public data: { message: string }
  ) {}
}

import { ManagerService } from '../../../core/services/manager.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Hall } from '../../../core/models';
import { LoadingSpinnerComponent } from '../../../shared/components/loading-spinner/loading-spinner.component';
import { ErrorMessageComponent } from '../../../shared/components/error-message/error-message.component';

@Component({
  selector: 'app-manager-halls',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatTooltipModule,
    MatDialogModule,
    LoadingSpinnerComponent,
    ErrorMessageComponent
  ],
  templateUrl: './manager-halls.component.html',
  styleUrls: ['./manager-halls.component.scss']
})
export class ManagerHallsComponent implements OnInit {
  private readonly managerService = inject(ManagerService);
  private readonly notificationService = inject(NotificationService);
  private readonly dialog = inject(MatDialog);
  private readonly destroyRef = inject(DestroyRef);

  halls: Hall[] = [];
  isLoading = false;
  error: string | null = null;
  actionInProgress: string | null = null;

  readonly displayedColumns = ['name', 'business_park', 'capacity', 'area', 'price', 'status', 'actions'];

  ngOnInit(): void {
    this.loadHalls();
  }

  loadHalls(): void {
    this.isLoading = true;
    this.error = null;
    this.managerService.getHalls()
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.isLoading = false),
        catchError(() => {
          this.error = 'Ошибка загрузки залов';
          return of([]);
        })
      )
      .subscribe(halls => {
        this.halls = halls;
      });
  }

  deactivateHall(hall: Hall): void {
    if (this.actionInProgress) return;

    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      width: '360px',
      data: { message: `Вы уверены, что хотите деактивировать зал "${hall.name}"?` }
    });

    dialogRef.afterClosed()
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        switchMap(confirmed => {
          if (!confirmed) return of(null);
          this.actionInProgress = hall.id;
          return this.managerService.deactivateHall(hall.id).pipe(
            finalize(() => this.actionInProgress = null),
            catchError(err => {
              this.notificationService.error(err?.error?.message || 'Ошибка деактивации зала');
              return of(null);
            })
          );
        })
      )
      .subscribe(result => {
        if (result !== null && result !== undefined) {
          this.notificationService.success(`Зал "${hall.name}" деактивирован`);
          this.halls = this.halls.filter(h => h.id !== hall.id);
        }
      });
  }

  getStatusLabel(status: string): string {
    return status === 'available' ? 'Активен' : 'Недоступен';
  }
}
