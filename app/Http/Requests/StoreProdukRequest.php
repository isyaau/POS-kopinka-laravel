<?php

namespace App\Http\Requests;

use App\Models\Produk;
use Illuminate\Foundation\Http\FormRequest;

class StoreProdukRequest extends FormRequest
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
        return [
            'kode_barang' => ['nullable', 'string', 'max:50', 'unique:produk,kode_barang'],
            'nama_barang' => ['required', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'no_rak' => ['nullable', 'string', 'max:50'],
            'harga_beli' => ['nullable', 'numeric', 'min:0'],
            'harga_jual' => ['nullable', 'numeric', 'min:0'],
            'diskon' => ['nullable', 'numeric', 'min:0'],
            'stok' => ['nullable', 'integer', 'min:0'],
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
            'tanggal_expired' => ['nullable', 'date'],
            'ppn' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ];
    }

    /**
     * Auto-generate kode barang jika kosong & normalkan field numerik.
     *
     * Kolom numerik di tabel dibuat NOT NULL dengan default 0, jadi nilai kosong
     * dari form ('' / null) diubah menjadi 0 agar tidak melanggar constraint.
     */
    protected function prepareForValidation(): void
    {
        $kode = trim($this->input('kode_barang') ?? '');

        if ($kode === '') {
            $this->merge(['kode_barang' => (new Produk())->generateKode()]);
        } else {
            // Normalisasi: hanya angka, tanpa spasi/karakter lain, pad ke 6 digit
            $this->merge(['kode_barang' => str_pad(preg_replace('/\D/', '', $kode), 6, '0', STR_PAD_LEFT)]);
        }

        foreach (['harga_beli', 'harga_jual', 'diskon', 'stok', 'stok_minimum', 'ppn'] as $field) {
            $value = $this->input($field);
            if ($value === null || $value === '') {
                $this->merge([$field => 0]);
            }
        }
    }
}
