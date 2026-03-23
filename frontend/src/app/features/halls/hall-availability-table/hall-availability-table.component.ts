import { Component, Input, Output, EventEmitter, OnInit, OnChanges, SimpleChanges, inject, DestroyRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { HallsService, HallAvailabilityResponse } from '../../../core/services/halls.service';
import { BookingStateService } from '../../../core/services/booking-state.service';
import { LoadingSpinnerComponent } from '../../../shared/components/loading-spinner/loading-spinner.component';
import { MatButtonModule } from '@angular/material/button';

@Component({
  selector: 'app-hall-availability-table',
  standalone: true,
  imports: [CommonModule, MatButtonModule, LoadingSpinnerComponent],
  templateUrl: './hall-availability-table.component.html',
  styleUrls: ['./hall-availability-table.component.scss']
})
export class HallAvailabilityTableComponent implements OnInit, OnChanges {
  private readonly hallsService = inject(HallsService);
  private readonly bookingState = inject(BookingStateService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) hallId!: string;
  @Input() refreshTrigger: unknown;
  @Output() slotSelected = new EventEmitter<{ date: string; startTime: string; endTime: string }>();

  data: HallAvailabilityResponse | null = null;
  isLoading = false;
  weekOffset = 0;

  ngOnInit(): void {
    this.loadAvailability();
    this.bookingState.bookingCreated
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(hallId => {
        if (hallId === this.hallId) {
          this.loadAvailability();
        }
      });
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['refreshTrigger'] && this.hallId) {
      this.loadAvailability();
    }
  }

  get fromDate(): string {
    const d = new Date();
    d.setDate(d.getDate() + this.weekOffset * 7);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  get dayLabels(): { date: string; label: string }[] {
    if (!this.data) return [];
    return Object.keys(this.data.availability).map(date => ({
      date,
      label: this.formatDayLabel(date)
    }));
  }

  formatDayLabel(dateStr: string): string {
    const d = new Date(dateStr + 'T12:00:00');
    const day = d.getDate().toString().padStart(2, '0');
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const year = d.getFullYear().toString().slice(-2);
    const weekdays = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
    const wd = weekdays[d.getDay()];
    return `${day}.${month}.${year} (${wd})`;
  }

  loadAvailability(): void {
    this.isLoading = true;
    this.hallsService.getHallAvailability(this.hallId, this.fromDate, 7)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: res => {
          this.data = res;
          this.isLoading = false;
        },
        error: () => {
          this.isLoading = false;
        }
      });
  }

  prevWeek(): void {
    if (this.weekOffset > 0) {
      this.weekOffset--;
      this.loadAvailability();
    }
  }

  nextWeek(): void {
    this.weekOffset++;
    this.loadAvailability();
  }

  canGoPrev(): boolean {
    return this.weekOffset > 0;
  }

  isSlotFree(date: string, slotIndex: number): boolean {
    const row = this.data?.availability[date];
    if (!row || !Array.isArray(row)) {
      return false;
    }
    return row[slotIndex] === true;
  }

  onSlotClick(date: string, slotIndex: number): void {
    if (!this.isSlotFree(date, slotIndex)) return;
    const slot = this.data?.time_slots[slotIndex];
    if (!slot) return;
    this.slotSelected.emit({
      date,
      startTime: slot.start,
      endTime: slot.end
    });
  }
}
