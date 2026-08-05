<?php

namespace App\Http\Requests;

use App\Models\Anggota;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnggotaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:anggota,karyawan'],
            'nip' => ['nullable', 'string', 'max:50', 'unique:anggota,nip'],
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

    /**
     * Prepare the data for validation: auto-generate NIP if empty.
     */
    protected function prepareForValidation(): void
    {
        $nip = trim($this->input('nip') ?? '');

        if ($nip === '') {
            $model = new Anggota(['status' => $this->input('status', 'anggota')]);
            $this->merge(['nip' => $model->generateNip()]);
        }

        if ($this->input('limit_transaksi') === null) {
            $this->merge(['limit_transaksi' => null]);
        }
    }
}
