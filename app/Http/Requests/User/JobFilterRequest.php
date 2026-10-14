<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCandidate() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'position' => $this->input('position') ?: null,
            'location' => $this->input('location') ?: null,
            'salary' => $this->input('salary') ?: null,
            'keyword' => trim((string) $this->input('keyword')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'position' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', Rule::in(array_keys(config('recruiting.salary_bands')))],
            'keyword' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'position' => '職種',
            'location' => '勤務地',
            'salary' => '年収',
            'keyword' => 'キーワード',
        ];
    }
}
