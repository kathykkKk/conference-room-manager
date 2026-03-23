<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function build(): self
    {
        $this->booking->loadMissing(['hall', 'client']);

        return $this->subject('Бронирование подтверждено — '.($this->booking->hall?->name ?? 'зал'))
            ->view('emails.booking-confirmed');
    }
}
