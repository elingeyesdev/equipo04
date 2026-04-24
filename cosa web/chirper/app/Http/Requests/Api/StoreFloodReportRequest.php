<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreFloodReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->all() !== []) {
            return;
        }

        $decoded = json_decode((string) $this->getContent(), true);

        if (is_array($decoded)) {
            $this->merge($decoded);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'severity' => ['required', 'string', 'in:low,medium,high'],
        ];
    }
}
