<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportAnggotaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:2048', // 2MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File Excel/CSV wajib dipilih.',
            'file.mimes' => 'Format file harus xlsx, xls, atau csv.',
            'file.max' => 'Ukuran file maksimal 2MB.',
        ];
    }
}
