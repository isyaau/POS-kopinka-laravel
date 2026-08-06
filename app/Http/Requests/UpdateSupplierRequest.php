<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('supplier')->id;

        return [
            'kode' => ['required', 'string', 'max:50', "unique:suppliers,kode,{$id}"],
            'nama' => ['required', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'keterangan' => ['nullable', 'string'],
        ];
    }
}
