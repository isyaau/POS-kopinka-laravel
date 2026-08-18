<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRegisterTagihanPiutangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('register_tagihan_piutang')->id;

        return [
            'tgl_tagihan' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:register_tagihan_piutang,no_bukti,{$id}"],
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
}
