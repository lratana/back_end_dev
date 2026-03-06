<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'description' => ['nullable', 'string'],

            'location' => ['nullable', 'string', 'max:255'],

            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:10000'],

            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'images' => ['nullable', 'array'],

            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],

            'equipment' => ['nullable', 'array'],

            'equipment.*' => ['string', 'max:255'],
        ];
    }
}
