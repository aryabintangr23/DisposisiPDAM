<?php

namespace App\Http\Requests;

use App\Enums\ArahSurat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScanSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi role dicek di controller (hanya Staff).
        return true;
    }

    public function rules(): array
    {
        return [
            'berkas' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,heic',
                'max:'.(int) config('scan.max_upload_kb', 20480),
            ],
            'arah_surat' => [
                'nullable',
                Rule::in(array_column(ArahSurat::cases(), 'value')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'berkas.required' => 'Pilih foto atau PDF surat yang ingin dipindai.',
            'berkas.mimes' => 'Format scan harus PDF, JPG, PNG, atau HEIC.',
            'berkas.max' => 'Ukuran berkas scan maksimal '.$this->batasUkuranMb().'MB.',
        ];
    }

    private function batasUkuranMb(): int
    {
        return max(1, (int) ceil(((int) config('scan.max_upload_kb', 20480)) / 1024));
    }
}
