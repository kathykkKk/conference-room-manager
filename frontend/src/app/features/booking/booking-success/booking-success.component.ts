import { Component, OnInit, inject, DestroyRef } from '@angular/core';
import { CommonModule, DatePipe, CurrencyPipe } from '@angular/common';
// DatePipe and CurrencyPipe used in template via async pipe and date formatting
import { ActivatedRoute, RouterLink } from '@angular/router';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDividerModule } from '@angular/material/divider';

@Component({
  selector: 'app-booking-success',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    DatePipe,
    CurrencyPipe,
    MatCardModule,
    MatButtonModule,
    MatIconModule,
    MatDividerModule
  ],
  templateUrl: './booking-success.component.html',
  styleUrls: ['./booking-success.component.scss']
})
export class BookingSuccessComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly destroyRef = inject(DestroyRef);

  bookingId: string = '';

  ngOnInit(): void {
    this.route.queryParams
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(params => {
        this.bookingId = params['bookingId'] || '';
      });
  }
}
