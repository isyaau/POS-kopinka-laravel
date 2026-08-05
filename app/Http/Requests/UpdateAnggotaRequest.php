<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('anggota')->id;

        return [
            'status' => ['required', 'in:anggota,karyawan'],
            'nip' => ['required', 'string', 'max:50', "unique:anggota,nip,{$id}"],
            'nama' => ['required', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'pendidikan' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'divisi_pekerjaan' => ['nullable', 'string', 'max:150'],
            'status_purna' => ['sometimes', 'boolean'],
            'tgl_terdaftar' => ['required', 'date'],
            'tgl_pensiun' => ['nullable', 'date', 'after_or_equal:tgl_terdaftar'],
            'simpanan_pokok' => ['nullable', 'numeric', 'min:0'],
            'simpanan_wajib' => ['nullable', 'numeric', 'min:0'],
            'status_aktif' => ['sometimes', 'boolean'],
            'status_limit' => ['sometimes', 'boolean'],
            'limit_transaksi' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('limit_transaksi') === null) {
            $this->merge(['limit_transaksi' => null]);
        }
    }
}
