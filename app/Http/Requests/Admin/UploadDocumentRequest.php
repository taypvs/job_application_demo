<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'source_type' => ['required', 'in:file'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,docx,txt'],
        ];
    }

    public function attributes(): array
    {
        return __('messages.attributes.document');
    }

    public function messages(): array
    {
        return [
            'file.mimes' => __('messages.validation.document_mimes'),
            'file.required' => __('messages.validation.document_file_required'),
        ];
    }
}
