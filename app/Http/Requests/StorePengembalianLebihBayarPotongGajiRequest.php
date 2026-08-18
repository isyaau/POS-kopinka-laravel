<?php

namespace App\Http\Requests;

use App\Models\PengembalianLebihBayarPotongGaji;
use Illuminate\Foundation\Http\FormRequest;

class StorePengembalianLebihBayarPotongGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_pengembalian' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:pengembalian_lebih_bayar_potong_gaji,no_bukti'],
            'register_tagihan_id' => ['required', 'exists:register_tagihan_piutang,id'],
            'no_register_tagihan' => ['required', 'string', 'max:50'],
            'penerimaan_angsuran_potong_gaji_id' => ['nullable', 'exists:penerimaan_angsuran_potong_gaji,id'],
            'no_transaksi_potong_gaji' => ['nullable', 'string', 'max:50'],
            'anggota_id' => ['nullable', 'exists:anggota,id'],
            'kode_anggota' => ['nullable', 'string', 'max:50'],
            'nama_anggota' => ['nullable', 'string', 'max:150'],
            'unit_kerja' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'jumlah_lebih_bayar' => ['required', 'numeric', 'min:0'],
            'jumlah_pengembalian' => ['required', 'numeric', 'min:0'],
            'metode_pengembalian' => ['nullable', 'string', 'in:transfer,tunai,potong_gaji_berikutnya'],
            'status' => ['required', 'in:pending,diproses,selesai,batal'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (trim($this->input('no_transaksi') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_pengembalian', now()->toDateString());
            $model = new PengembalianLebihBayarPotongGaji();
            $this->merge(['no_transaksi' => $model->generateNoTransaksi($store?->kode, $tanggal)]);
        }

        if (trim($this->input('no_bukti') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_pengembalian', now()->toDateString());
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($store?->kode ?? 'PUSAT')));
            $this->merge(['no_bukti' => 'BLB' . $kode . str_replace('-', '', $tanggal) . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
