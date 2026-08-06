<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kode' => ['nullable', 'string', 'max:50', 'unique:suppliers,kode'],
            'nama' => ['required', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    /**
     * Prepare the data for validation: auto-generate kode if empty.
     */
    protected function prepareForValidation(): void
    {
        $kode = trim($this->input('kode') ?? '');

        if ($kode === '') {
            $this->merge(['kode' => (new Supplier())->generateKode()]);
        }
    }
}
