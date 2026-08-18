<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePengembalianLebihBayarPotongGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('pengembalian_lebih_bayar_potong_gaji')->id;

        return [
            'tgl_pengembalian' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:pengembalian_lebih_bayar_potong_gaji,no_bukti,{$id}"],
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
}
