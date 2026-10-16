<?php

namespace App\Http\Requests;

use App\Enums\BadgeCriteria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BadgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('badges', 'name')->ignore($this->route('badge'))],
            'description' => ['required', 'string', 'max:255'],
            'emoji' => ['required', 'string', 'max:16'],
            'criteria_type' => ['required', Rule::enum(BadgeCriteria::class)],
            'criteria_value' => ['required', 'integer', 'min:1', 'max:100000'],
            'category_id' => [
                Rule::requiredIf($this->input('criteria_type') === BadgeCriteria::CategoryCorrect->value),
                'nullable', 'exists:categories,id',
            ],
            'xp_bonus' => ['required', 'integer', 'min:0', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Only category badges keep a category.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated();

        if ($data['criteria_type'] !== BadgeCriteria::CategoryCorrect->value) {
            $data['category_id'] = null;
        }

        return data_get($data, $key, $default);
    }
}
