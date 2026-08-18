<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGagalDebetPiutangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('gagal_debet_piutang')->id;

        return [
            'tgl_gagal' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:gagal_debet_piutang,no_bukti,{$id}"],
            'register_tagihan_id' => ['required', 'exists:register_tagihan_piutang,id'],
            'no_register_tagihan' => ['required', 'string', 'max:50'],
            'anggota_id' => ['nullable', 'exists:anggota,id'],
            'kode_anggota' => ['nullable', 'string', 'max:50'],
            'nama_anggota' => ['nullable', 'string', 'max:150'],
            'unit_kerja' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'jumlah_gagal_debet' => ['required', 'numeric', 'min:0'],
            'alasan_gagal' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:pending,diproses,selesai,batal'],
            'tgl_followup' => ['nullable', 'date'],
            'catatan_followup' => ['nullable', 'string'],
        ];
    }
}
