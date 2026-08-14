<?php

namespace App\Http\Requests;

use App\Models\PenerimaanAngsuran;
use Illuminate\Foundation\Http\FormRequest;

class StorePenerimaanAngsuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_transaksi' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:penerimaan_angsuran,no_bukti'],
            'no_faktur' => ['nullable', 'string', 'max:50'],
            'anggota_id' => ['nullable', 'exists:anggota,id'],
            'kode_anggota' => ['nullable', 'string', 'max:50'],
            'nama_anggota' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'no_telp' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'tgl_jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tgl_transaksi'],
            'nilai_piutang' => ['required', 'numeric', 'min:0'],
            'retur_penjualan' => ['nullable', 'numeric', 'min:0'],
            'diskon_pembayaran' => ['nullable', 'numeric', 'min:0'],
            'total_harus_dibayar' => ['required', 'numeric', 'min:0'],
            'jumlah_bayar' => ['required', 'numeric', 'min:0'],
            'total_terbayar' => ['nullable', 'numeric', 'min:0'],
            'total_diskon' => ['nullable', 'numeric', 'min:0'],
            'kurang_bayar' => ['nullable', 'numeric', 'min:0'],
            'sisa_piutang' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    /**
     * Auto-generate no_transaksi & no_bukti bila kosong.
     */
    protected function prepareForValidation(): void
    {
        if (trim($this->input('no_transaksi') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_transaksi', now()->toDateString());
            $model = new PenerimaanAngsuran();
            $this->merge(['no_transaksi' => $model->generateNoTransaksi($store?->kode, $tanggal)]);
        }

        if (trim($this->input('no_bukti') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_transaksi', now()->toDateString());
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($store?->kode ?? 'PUSAT')));
            $this->merge(['no_bukti' => 'BA' . $kode . str_replace('-', '', $tanggal) . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
