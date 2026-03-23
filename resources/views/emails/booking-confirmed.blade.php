<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Подтверждение бронирования</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5;">
    <p>Бронирование <strong>{{ $booking->id }}</strong> подтверждено.</p>
    <p><strong>Зал:</strong> {{ $booking->hall?->name ?? '—' }}</p>
    <p><strong>Начало (UTC):</strong> {{ $booking->start_datetime?->format('Y-m-d H:i') }}</p>
    <p><strong>Окончание (UTC):</strong> {{ $booking->end_datetime?->format('Y-m-d H:i') }}</p>
    <p><strong>Сумма:</strong> {{ number_format((float) $booking->total_price, 2, '.', ' ') }} ₽</p>
    @if($booking->client)
        <p><strong>Клиент:</strong> {{ $booking->client->full_name }}, {{ $booking->client->email }}</p>
    @endif
</body>
</html>
