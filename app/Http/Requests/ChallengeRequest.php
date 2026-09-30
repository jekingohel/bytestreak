<?php

namespace App\Http\Requests;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeType;
use App\Enums\Difficulty;
use App\Models\Challenge;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'type' => ['required', Rule::enum(ChallengeType::class)],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'title' => ['required', 'string', 'max:160'],
            'question' => ['required', 'string', 'max:5000'],
            'code_snippet' => ['nullable', 'string', 'max:10000'],
            'code_language' => ['nullable', Rule::in(Challenge::CODE_LANGUAGES)],
            'explanation' => ['required', 'string', 'max:5000'],
            'xp' => ['required', 'integer', 'min:1', 'max:200'],
            'status' => ['required', Rule::enum(ChallengeStatus::class)],
            'publish_date' => ['nullable', 'date_format:Y-m-d', $this->dateIsFree(...)],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*.id' => ['nullable', 'integer'],
            'options.*.label' => ['required', 'string', 'max:1000'],
            'correct_index' => ['required', 'integer', 'min:0'],
        ];
    }

    /** Only one challenge may own a date. Compared as a date so MySQL and SQLite agree. */
    private function dateIsFree(string $attribute, mixed $value, Closure $fail): void
    {
        $taken = Challenge::whereDate('publish_date', $value)
            ->when($this->route('challenge'), fn ($query, Challenge $current) => $query->whereKeyNot($current->id))
            ->exists();

        if ($taken) {
            $fail('Another challenge already owns that date.');
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'options.*.label.required' => 'Every answer option needs some text.',
            'correct_index.required' => 'Mark one option as the correct answer.',
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ((int) $this->input('correct_index') >= count($this->input('options', []))) {
                    $validator->errors()->add('correct_index', 'Mark one option as the correct answer.');
                }

                $status = ChallengeStatus::from($this->input('status'));
                $date = $this->input('publish_date') ? Carbon::parse($this->input('publish_date')) : null;
                $existing = $this->route('challenge');
                $dateChanged = $date?->toDateString() !== $existing?->publish_date?->toDateString();

                if ($status === ChallengeStatus::Scheduled && $date && $dateChanged && $date->lt(today())) {
                    $validator->errors()->add('publish_date', 'A scheduled challenge needs today or a future date.');
                }

                if ($status === ChallengeStatus::Published && $date?->gt(today())) {
                    $validator->errors()->add('publish_date', 'A published challenge cannot have a future date — choose Scheduled instead.');
                }
            },
        ];
    }
}
