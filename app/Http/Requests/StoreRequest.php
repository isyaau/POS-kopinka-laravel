<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        $storeId = $this->route('store')?->id;

        return [
            'kode' => ['required', 'string', 'max:20', 'unique:stores,kode,' . $storeId],
            'nama' => ['required', 'string', 'max:150'],
            'tipe' => ['required', 'in:pusat,toko'],
            'alamat' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Normalisasi kode menjadi huruf besar.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('kode')) {
            $this->merge([
                'kode' => strtoupper(trim($this->input('kode'))),
            ]);
        }
    }
}
