/**
 * Календарная дата (как в datepicker) + время как часы в UTC (+0).
 * Итог — ISO 8601 с Z; на бэкенде слоты и брони считаются в UTC.
 */
export function formatUtcDatetimeFromCalendarDateAndTime(calendarDate: Date, timeHHmm: string): string {
  const y = calendarDate.getFullYear();
  const m = calendarDate.getMonth();
  const d = calendarDate.getDate();
  const [hh, mm = '0'] = timeHHmm.split(':');
  const h = Number(hh);
  const min = Number(mm);
  return new Date(Date.UTC(y, m, d, h, min, 0, 0)).toISOString();
}

/** Локальная календарная дата YYYY-MM-DD для фильтра «только дата» (день из календаря). */
export function formatLocalDateOnly(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}
