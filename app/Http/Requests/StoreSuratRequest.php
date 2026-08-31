<?php

namespace App\Http\Requests;

use App\Enums\ArahSurat;
use App\Enums\Prioritas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi role (hanya Staff) dicek di controller, bukan di sini.
        return true;
    }

    public function rules(): array
    {
        return [
            'arah_surat' => ['required', Rule::in(array_column(ArahSurat::cases(), 'value'))],
            'jenis_surat' => ['required', 'string', 'max:255'],
            'nomor_surat' => ['required', 'string', 'max:255'],
            'nomor_agenda' => ['nullable', 'string', 'max:255'],
            'tanggal_surat' => ['required', 'date'],
            'tanggal_diterima' => ['nullable', 'date'],

            // surat_dari relevan untuk surat masuk, tujuan_surat untuk surat keluar.
            'surat_dari' => ['nullable', 'required_if:arah_surat,masuk', 'string', 'max:255'],
            'tujuan_surat' => ['nullable', 'required_if:arah_surat,keluar', 'string', 'max:255'],

            'perihal' => ['required', 'string'],

            // PDF saja, maksimal 10MB per file (dikonfirmasi sebelumnya: PDF saja).
            'lampiran.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],

            // Lembar disposisi pertama (Staff -> Kabag), dibuat bersamaan dengan surat.
            'penerima_id' => ['required', 'exists:users,id'],
            'prioritas' => ['required', Rule::in(array_column(Prioritas::cases(), 'value'))],
            'instruksi' => ['nullable', 'string'],
        ];
    }
}
