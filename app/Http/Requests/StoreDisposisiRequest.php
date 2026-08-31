<?php

namespace App\Http\Requests;

use App\Enums\Prioritas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisposisiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kombinasi arah dicek via DisposisiRuleService di controller.
    }

    public function rules(): array
    {
        return [
            'penerima_id' => ['required', 'exists:users,id'],
            'prioritas' => ['required', Rule::in(array_column(Prioritas::cases(), 'value'))],
            'instruksi' => ['nullable', 'string'],
        ];
    }
}
