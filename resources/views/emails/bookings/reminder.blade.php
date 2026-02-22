@component('mail::message')
    # Check-in Reminder

    Hi {{ $booking->guest_name }},

    This is a friendly reminder that your check-in is **tomorrow**!

    **Booking Details:**
    - Property: {{ $booking->building->name }}
    - Unit: {{ $booking->unitType->name }} - Unit {{ $booking->unit->unit_number }}
    - Check-in: {{ $booking->check_in->format('l, F j, Y') }}
    - Check-out: {{ $booking->check_out->format('l, F j, Y') }}

    @component('mail::button', ['url' => route('bookings.show', $booking->booking_reference)])
        View Booking Details
    @endcomponent

    We look forward to welcoming you!

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
