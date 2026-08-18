<?php

namespace App\Http\Requests;

use App\Models\GagalDebetPiutang;
use Illuminate\Foundation\Http\FormRequest;

class StoreGagalDebetPiutangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_gagal' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:gagal_debet_piutang,no_bukti'],
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

    protected function prepareForValidation(): void
    {
        if (trim($this->input('no_transaksi') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_gagal', now()->toDateString());
            $model = new GagalDebetPiutang();
            $this->merge(['no_transaksi' => $model->generateNoTransaksi($store?->kode, $tanggal)]);
        }

        if (trim($this->input('no_bukti') ?? '') === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tgl_gagal', now()->toDateString());
            $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($store?->kode ?? 'PUSAT')));
            $this->merge(['no_bukti' => 'BDG' . $kode . str_replace('-', '', $tanggal) . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT)]);
        }
    }
}
