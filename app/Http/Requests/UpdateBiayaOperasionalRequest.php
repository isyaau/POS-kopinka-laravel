<?php

namespace App\Http\Requests;

use App\Models\BiayaOperasional;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBiayaOperasionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('biaya_operasional')->id;

        return [
            'tanggal' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:50', "unique:biaya_operasional,no_bukti,{$id}"],
            'dari_unit' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'string', 'max:100', 'in:' . implode(',', BiayaOperasional::kategoriList())],
            'keterangan' => ['nullable', 'string'],
            'jumlah' => ['required', 'numeric', 'min:0'],
        ];
    }
}
