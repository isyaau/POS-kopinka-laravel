<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
{
    /**
     * Authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi: kode/barcode kosong → generate otomatis.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => $this->input('kode') ?: (new \App\Models\Voucher())->generateKode(),
            'barcode' => $this->input('barcode') ?: (new \App\Models\Voucher())->generateBarcode(),
        ]);
    }

    /**
     * Aturan validasi.
     */
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:50', 'unique:vouchers,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'barcode' => ['required', 'string', 'max:50', 'unique:vouchers,barcode'],
            'status' => ['sometimes', 'in:aktif,terpakai,kedaluwarsa'],
            'tanggal_expired' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string'],
            'store_id' => ['nullable', 'exists:stores,id'],
        ];
    }

    /**
     * Pesan error bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Kode voucher wajib diisi.',
            'kode.unique' => 'Kode voucher sudah digunakan.',
            'nama.required' => 'Nama kupon wajib diisi.',
            'nominal.required' => 'Nominal wajib diisi.',
            'nominal.min' => 'Nominal tidak boleh negatif.',
            'barcode.required' => 'Barcode wajib diisi.',
            'barcode.unique' => 'Barcode sudah digunakan.',
        ];
    }
}
