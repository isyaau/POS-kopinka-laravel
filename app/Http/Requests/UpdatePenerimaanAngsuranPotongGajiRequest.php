<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePenerimaanAngsuranPotongGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('penerimaan_angsuran_potong_gaji')->id;

        return [
            'tgl_transaksi' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:penerimaan_angsuran_potong_gaji,no_bukti,{$id}"],
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
}
