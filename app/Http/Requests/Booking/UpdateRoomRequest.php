<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->name),
            ]);
        }

        if ($this->has('location')) {
            $this->merge([
                'location' => trim((string) $this->location),
            ]);
        }

        if ($this->has('description')) {
            $this->merge([
                'description' => trim((string) $this->description),
            ]);
        }

        if ($this->has('equipment') && is_array($this->equipment)) {
            $equipment = collect($this->equipment)
                ->map(fn($item) => is_string($item) ? trim($item) : $item)
                ->filter(fn($item) => $item !== null && $item !== '')
                ->values()
                ->all();

            $this->merge([
                'equipment' => $equipment,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'department_id' => ['sometimes', 'required', 'integer', 'exists:departments,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],

            'equipment' => ['sometimes', 'array'],
            'equipment.*' => ['nullable', 'string', 'max:255'],

            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.exists' => 'Selected department is invalid.',
            'name.required' => 'Room name is required.',
            'capacity.required' => 'Capacity is required.',
            'capacity.integer' => 'Capacity must be a number.',
            'capacity.min' => 'Capacity must be at least 1.',
            'thumbnail.image' => 'Thumbnail must be an image.',
            'thumbnail.mimes' => 'Thumbnail must be a jpg, jpeg, png, or webp file.',
            'thumbnail.max' => 'Thumbnail may not be greater than 2MB.',
            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.mimes' => 'Each image must be a jpg, jpeg, png, or webp file.',
            'images.*.max' => 'Each image may not be greater than 4MB.',
        ];
    }
}
