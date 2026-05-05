<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $menu = $this->route('menu');
        $menuId = is_object($menu) ? $menu->id : $menu;

        return [
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
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
