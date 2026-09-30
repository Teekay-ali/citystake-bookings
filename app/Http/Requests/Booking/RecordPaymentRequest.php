<?php

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access control (permission + building scope) is enforced in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'            => ['required', 'numeric', 'min:1'],
            'payment_method'    => ['required', Rule::in(Booking::COUNTER_PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
