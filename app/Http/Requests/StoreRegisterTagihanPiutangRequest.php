<?php

namespace App\Http\Requests;

use App\Models\RegisterTagihanPiutang;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegisterTagihanPiutangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_tagihan' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:register_tagihan_piutang,no_bukti'],
            'no_faktur' => ['nullable', 'string', 'max:50'],
            'anggota_id' => ['nullable', 'exists:anggota,id'],
            'kode_anggota' => ['nullable', 'string', 'max:50'],
            'nama_anggota' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'unit_kerja' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'nilai_piutang' => ['required', 'numeric', 'min:0'],
            'retur_penjualan' => ['nullable', 'numeric', 'min:0'],
            'diskon_pembayaran' => ['nullable', 'numeric', 'min:0'],
            'total_harus_dibayar' => ['required', 'numeric', 'min:0'],
            'total_terbayar' => ['nullable', 'numeric', 'min:0'],
            'total_diskon' => ['nullable', 'numeric', 'min:0'],
            'sisa_piutang' => ['nullable', 'numeric', 'min:0'],
            'periode_potong' => ['nullable', 'string', 'max:50'],
            'jumlah_potong_per_bulan' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Auto-generate no_transaksi & no_bukti bila kosong.
     */
    protected function prepareForValidation(): void
    {
        if (trim($this->input('no_transaksi') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_tagihan', now()->toDateString());
            $model = new RegisterTagihanPiutang();
            $this->merge(['no_transaksi' => $model->generateNoTransaksi($store?->kode, $tanggal)]);
        }

        if (trim($this->input('no_bukti') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_tagihan', now()->toDateString());
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($store?->kode ?? 'PUSAT')));
            $this->merge(['no_bukti' => 'BT' . $kode . str_replace('-', '', $tanggal) . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
