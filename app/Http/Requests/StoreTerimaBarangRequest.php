<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTerimaBarangRequest extends FormRequest
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
            'no_surat_jalan' => ['nullable', 'string', 'max:100'],
            'tipe' => ['nullable', Rule::in(['konsinyasi', 'retur_toko'])],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'nama_supplier' => ['nullable', 'string', 'max:150'],
            'tanggal' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'selesai', 'batal'])],
            'keterangan' => ['nullable', 'string'],

            // Items (detail penerimaan)
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'items.*.nama_barang' => ['nullable', 'string', 'max:150', 'required_without:items.*.produk_id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.tanggal_expired' => ['nullable', 'date'],
        ];
    }

    /**
     * Pastikan setiap item memiliki produk (id) ATAU nama barang (produk baru).
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            foreach ($items as $index => $item) {
                $produkId = $item['produk_id'] ?? null;
                $nama = trim((string) ($item['nama_barang'] ?? ''));
                if (! $produkId && $nama === '') {
                    $validator->errors()->add("items.{$index}.produk_id", 'Pilih produk atau ketik nama produk baru.');
                }
            }
        });
    }
}
