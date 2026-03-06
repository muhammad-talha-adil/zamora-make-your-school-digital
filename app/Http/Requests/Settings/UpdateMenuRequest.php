<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
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
        $menuId = $this->route('id');

        return [
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'path' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'parent_id' => [
                'nullable',
                'exists:menus,id',
                Rule::notIn([$menuId]),
            ],
            'order' => 'integer|min:0',
            'type' => 'required|in:main,footer',
        ];
    }
}