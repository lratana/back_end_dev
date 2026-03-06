<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:0', 'max:10000'],
            'thumbnail' => ['nullable', 'image', 'max:5120'],
            'images.*' => ['nullable', 'image', 'max:6144'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['string', 'max:255'],
        ];
    }
}
