<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
{
    /**
     * Authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi (unique kecuali id sendiri).
     */
    public function rules(): array
    {
        $id = $this->route('voucher')?->id ?? $this->route('voucher');

        return [
            'kode' => ['required', 'string', 'max:50', 'unique:vouchers,kode,' . $id],
            'nama' => ['required', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'barcode' => ['required', 'string', 'max:50', 'unique:vouchers,barcode,' . $id],
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
