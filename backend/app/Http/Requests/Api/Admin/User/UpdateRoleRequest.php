<?php

namespace App\Http\Requests\Api\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|in:admin,editor,user',
        ];
    }
} 