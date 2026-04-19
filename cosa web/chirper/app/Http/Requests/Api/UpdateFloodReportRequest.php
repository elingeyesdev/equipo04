<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFloodReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $baseRules = [
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'severity' => ['sometimes', 'string', 'in:low,medium,high'],
        ];

        if ($this->user()?->isAuthority()) {
            $baseRules['status'] = ['sometimes', 'string', 'in:open,in_progress,resolved,closed'];
        }

        return $baseRules;
    }
}
