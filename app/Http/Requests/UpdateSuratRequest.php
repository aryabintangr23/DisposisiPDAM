<?php

namespace App\Http\Requests;

use App\Enums\ArahSurat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi role & kepemilikan surat dicek di controller, bukan di sini.
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

            // surat_dari hanya relevan untuk surat masuk, tujuan_surat untuk
            // surat keluar — field yang tidak relevan disembunyikan di form
            // (lihat JS di surat/edit.blade.php) dan tidak wajib diisi.
            'surat_dari' => ['nullable', 'required_if:arah_surat,masuk', 'string', 'max:255'],
            'tujuan_surat' => ['nullable', 'required_if:arah_surat,keluar', 'string', 'max:255'],

            'perihal' => ['required', 'string'],

            // PDF, JPG, dan DOCX, maksimal 10MB per file. Lampiran baru
            // ditambahkan ke lampiran yang sudah ada (tidak menggantikan).
            'lampiran.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,docx', 'max:10240'],
        ];
    }
}
