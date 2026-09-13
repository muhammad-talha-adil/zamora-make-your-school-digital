<?php

namespace App\Http\Requests\Settings;

use App\Models\Menu;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $menuId = $this->routeMenuId();

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

    /**
     * Reject a parent_id that is one of the menu's own descendants, which
     * would otherwise create a circular parent chain and hang any code that
     * walks it (e.g. `MenuController::buildMenuLabel()`).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parentId = $this->input('parent_id');
            $menuId = $this->routeMenuId();

            if (! $parentId || ! $menuId) {
                return;
            }

            $ancestorId = $parentId;
            $visited = [];

            while ($ancestorId) {
                if ((int) $ancestorId === (int) $menuId) {
                    $validator->errors()->add('parent_id', 'The selected parent menu would create a circular reference.');

                    return;
                }

                if (isset($visited[$ancestorId])) {
                    return;
                }

                $visited[$ancestorId] = true;
                $ancestorId = Menu::withTrashed()->whereKey($ancestorId)->value('parent_id');
            }
        });
    }

    private function routeMenuId(): ?int
    {
        $menu = $this->route('menu');

        return $menu ? (int) (is_object($menu) ? $menu->id : $menu) : null;
    }
}
