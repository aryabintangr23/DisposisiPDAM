<?php

namespace App\Http\Requests;

use App\Enums\Prioritas;
use App\Enums\StatusSurat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisposisiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kombinasi arah & keputusan dicek via DisposisiRuleService di controller.
    }

    public function rules(): array
    {
        return [
            'penerima_id' => ['required', 'exists:users,id'],
            'prioritas' => ['required', Rule::in(array_column(Prioritas::cases(), 'value'))],
            'instruksi' => ['nullable', 'string'],

            // Opsional: keputusan surat yang menyertai disposisi ini, mis.
            // Kabag -> Staff bisa menandai "perlu_revisi", Direktur -> Kabag
            // bisa menandai "diterima"/"ditolak". Validasi kombinasi role
            // dilakukan di DisposisiRuleService, bukan di sini.
            'keputusan_surat' => ['nullable', Rule::in(array_column(StatusSurat::cases(), 'value'))],
        ];
    }
}
