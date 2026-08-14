<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePenerimaanAngsuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('penerimaan_angsuran')->id;

        return [
            'tgl_transaksi' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:penerimaan_angsuran,no_bukti,{$id}"],
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
}
