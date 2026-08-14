<?php

namespace App\Http\Requests;

use App\Models\BiayaOperasional;
use Illuminate\Foundation\Http\FormRequest;

class StoreBiayaOperasionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'no_bukti' => ['nullable', 'string', 'max:50', 'unique:biaya_operasional,no_bukti'],
            'dari_unit' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'string', 'max:100', 'in:' . implode(',', BiayaOperasional::kategoriList())],
            'keterangan' => ['nullable', 'string'],
            'jumlah' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Auto-generate no_bukti bila kosong, agar user tak perlu mengisi manual.
     */
    protected function prepareForValidation(): void
    {
        $noBukti = trim($this->input('no_bukti') ?? '');

        if ($noBukti === '') {
            $store = auth()->user()?->store;
            $tanggal = $this->input('tanggal', now()->toDateString());
            $model = new BiayaOperasional();
            $this->merge(['no_bukti' => $model->generateNoBukti($store?->kode, $tanggal)]);
        }
    }
}
