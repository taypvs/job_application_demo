<?php

namespace App\Http\Requests\Admin;

use App\Enums\JobStatus;
use App\Enums\RemotePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'required_skills' => $this->lines('required_skills_text'),
            'nice_to_have_skills' => $this->lines('nice_to_have_skills_text'),
        ]);
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:100'],
            'employment_type' => ['required', Rule::in(config('recruiting.employment_types'))],
            'summary' => ['required', 'string', 'max:2000'],
            'description' => ['required', 'string', 'max:5000'],
            'background' => ['nullable', 'string', 'max:3000'],
            'requirements' => ['nullable', 'string', 'max:3000'],
            'appeal' => ['nullable', 'string', 'max:3000'],
            'company_note' => ['nullable', 'string', 'max:3000'],
            'working_hours' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:100'],
            'remote_policy' => ['required', Rule::enum(RemotePolicy::class)],
            'salary_min_man' => ['required', 'integer', 'min:0', 'max:10000'],
            'salary_max_man' => ['required', 'integer', 'gte:salary_min_man', 'max:10000'],
            'salary_note' => ['nullable', 'string', 'max:255'],
            'benefits' => ['nullable', 'string', 'max:3000'],
            'holidays' => ['nullable', 'string', 'max:2000'],
            'probation' => ['nullable', 'string', 'max:255'],
            'selection_process' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(JobStatus::class)],
            'required_skills' => ['nullable', 'array'],
            'required_skills.*' => ['string', 'max:100'],
            'nice_to_have_skills' => ['nullable', 'array'],
            'nice_to_have_skills.*' => ['string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => '会社',
            'department_id' => '担当部署',
            'title' => '募集タイトル',
            'position' => '職種',
            'employment_type' => '雇用形態',
            'summary' => '概要',
            'description' => '仕事内容',
            'working_hours' => '勤務時間',
            'location' => '勤務地',
            'remote_policy' => '働き方',
            'salary_min_man' => '年収下限',
            'salary_max_man' => '年収上限',
            'status' => '公開状態',
        ];
    }

    /**
     * @return list<string>
     */
    private function lines(string $key): array
    {
        $items = preg_split('/\r\n|\r|\n/', (string) $this->input($key, '')) ?: [];

        return array_values(array_filter(array_map('trim', $items)));
    }
}
