import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: '',
    loadComponent: () => import('./layout/main-layout/main-layout.component').then(m => m.MainLayoutComponent),
    children: [
      {
        path: '',
        loadComponent: () => import('./features/halls/halls-list/halls-list.component').then(m => m.HallsListComponent)
      },
      {
        path: 'halls/:id',
        loadComponent: () => import('./features/halls/hall-detail/hall-detail.component').then(m => m.HallDetailComponent)
      },
      {
        path: 'booking/new',
        loadComponent: () => import('./features/booking/booking-form/booking-form.component').then(m => m.BookingFormComponent)
      },
      {
        path: 'booking/success',
        loadComponent: () => import('./features/booking/booking-success/booking-success.component').then(m => m.BookingSuccessComponent)
      },
    ]
  },
  {
    path: 'manager/login',
    loadComponent: () => import('./features/manager/login/login.component').then(m => m.LoginComponent)
  },
  {
    path: 'manager',
    canActivate: [authGuard],
    loadComponent: () => import('./layout/manager-layout/manager-layout.component').then(m => m.ManagerLayoutComponent),
    children: [
      { path: '', redirectTo: 'bookings', pathMatch: 'full' },
      {
        path: 'bookings',
        loadComponent: () => import('./features/manager/bookings/manager-bookings.component').then(m => m.ManagerBookingsComponent)
      },
      {
        path: 'halls',
        loadComponent: () => import('./features/manager/halls/manager-halls.component').then(m => m.ManagerHallsComponent)
      },
      {
        path: 'halls/new',
        loadComponent: () => import('./features/manager/hall-form/hall-form.component').then(m => m.HallFormComponent)
      },
      {
        path: 'halls/:id/edit',
        loadComponent: () => import('./features/manager/hall-form/hall-form.component').then(m => m.HallFormComponent)
      },
      {
        path: 'reports',
        loadComponent: () => import('./features/manager/reports/manager-reports.component').then(m => m.ManagerReportsComponent)
      },
    ]
  },
  { path: '**', redirectTo: '' }
];
