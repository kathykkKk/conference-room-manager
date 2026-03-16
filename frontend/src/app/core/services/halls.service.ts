import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { Hall, HallFiltersData } from '../models';

export interface HallSearchParams {
  business_park_id?: string;
  capacity_from?: number;
  capacity_to?: number;
  city?: string;
  equipment_category?: string[];
  min_price?: number;
  max_price?: number;
  date_range_start?: string;
  date_range_end?: string;
}

@Injectable({ providedIn: 'root' })
export class HallsService {
  constructor(private http: HttpClient) {}

  getFilters(): Observable<HallFiltersData> {
    return this.http.get<HallFiltersData>('/api/v1/halls/filters');
  }

  searchHalls(params: HallSearchParams): Observable<Hall[]> {
    let httpParams = new HttpParams();
    if (params.business_park_id) {
      httpParams = httpParams.set('business_park_id', params.business_park_id);
    }
    if (params.capacity_from != null) {
      httpParams = httpParams.set('capacity_from', params.capacity_from);
    }
    if (params.capacity_to != null) {
      httpParams = httpParams.set('capacity_to', params.capacity_to);
    }
    if (params.city) {
      httpParams = httpParams.set('city', params.city);
    }
    if (params.min_price != null) {
      httpParams = httpParams.set('min_price', params.min_price);
    }
    if (params.max_price != null) {
      httpParams = httpParams.set('max_price', params.max_price);
    }
    if (params.date_range_start) {
      httpParams = httpParams.set('date_range[start]', params.date_range_start);
    }
    if (params.date_range_end) {
      httpParams = httpParams.set('date_range[end]', params.date_range_end);
    }
    if (params.equipment_category?.length) {
      params.equipment_category.forEach((e: string) => {
        httpParams = httpParams.append('equipment_category[]', e);
      });
    }
    return this.http.get<{ data: Hall[] }>('/api/v1/halls', { params: httpParams }).pipe(
      map((response: { data: Hall[] }) => response.data)
    );
  }

  getHall(id: string): Observable<Hall> {
    return this.http.get<Hall>(`/api/v1/halls/${id}`);
  }
}
