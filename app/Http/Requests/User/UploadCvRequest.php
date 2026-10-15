<?php

namespace App\Http\Requests\User;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UploadCvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCandidate() ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', $this->allowedCvFile()],
        ];
    }

    public function attributes(): array
    {
        return __('messages.attributes.cv');
    }

    private function allowedCvFile(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $message = __('messages.validation.cv_file');

            if (! $value instanceof UploadedFile) {
                $fail($message);

                return;
            }

            $extension = strtolower($value->getClientOriginalExtension());
            $allowed = ['pdf', 'docx', 'txt', 'xlsx', 'xls'];
            $guessed = strtolower((string) $value->guessExtension());
            $excelDetectedAsZip = in_array($extension, ['xlsx', 'xls'], true) && $guessed === 'zip';

            if (! in_array($extension, $allowed, true) || (! in_array($guessed, $allowed, true) && ! $excelDetectedAsZip)) {
                $fail($message);
            }
        };
    }
}
