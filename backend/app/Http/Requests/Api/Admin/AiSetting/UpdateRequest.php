<?php

namespace App\Http\Requests\Api\Admin\AiSetting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'api_key' => 'required|string',
            'model' => 'required|string',
            'temperature' => 'required|numeric|min:0|max:1',
            'max_tokens' => 'required|integer|min:1',
            'prompt_template' => 'required|string',
        ];
    }
}
