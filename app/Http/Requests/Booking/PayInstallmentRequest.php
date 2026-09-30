<?php

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method'    => ['required', Rule::in(Booking::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
