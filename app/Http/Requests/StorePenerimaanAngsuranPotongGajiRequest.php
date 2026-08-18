<?php

namespace App\Http\Requests;

use App\Models\PenerimaanAngsuranPotongGaji;
use Illuminate\Foundation\Http\FormRequest;

class StorePenerimaanAngsuranPotongGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_transaksi' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:penerimaan_angsuran_potong_gaji,no_bukti'],
            'register_tagihan_id' => ['required', 'exists:register_tagihan_piutang,id'],
            'no_register_tagihan' => ['required', 'string', 'max:50'],
            'anggota_id' => ['nullable', 'exists:anggota,id'],
            'kode_anggota' => ['nullable', 'string', 'max:50'],
            'nama_anggota' => ['nullable', 'string', 'max:150'],
            'unit_kerja' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'jumlah_potong' => ['required', 'numeric', 'min:0'],
            'total_terbayar_sebelum' => ['required', 'numeric', 'min:0'],
            'total_terbayar_sesudah' => ['required', 'numeric', 'min:0'],
            'sisa_piutang' => ['required', 'numeric', 'min:0'],
            'periode_gaji' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (trim($this->input('no_transaksi') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_transaksi', now()->toDateString());
            $model = new PenerimaanAngsuranPotongGaji();
            $this->merge(['no_transaksi' => $model->generateNoTransaksi($store?->kode, $tanggal)]);
        }

        if (trim($this->input('no_bukti') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_transaksi', now()->toDateString());
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($store?->kode ?? 'PUSAT')));
            $this->merge(['no_bukti' => 'BAGP' . $kode . str_replace('-', '', $tanggal) . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
