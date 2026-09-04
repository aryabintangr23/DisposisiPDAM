<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi role (hanya Admin) dicek lewat middleware 'admin', bukan di sini.
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            // Hanya boleh satu Admin; tidak diizinkan mengubah user lain menjadi Admin.
            'role_id' => ['required', 'exists:roles,id', function ($attribute, $value, $fail) {
                if (Role::where('id', $value)->value('nama_role') === 'admin') {
                    $fail('Hanya boleh ada satu Admin, jadi role Admin tidak bisa dipilih.');
                }
            }],
            // Password opsional saat edit: kosong = tidak diubah.
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
