<?php

namespace App\Http\Requests\Issues;

use App\Enums\ResolutionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveIssueRequest extends FormRequest
{
    /**
     * Authorization is handled by the controller via authorize().
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'root_cause' => ['required', 'string', 'min:10', 'max:2000'],
            'steps_taken' => ['nullable', 'array', 'max:10'],
            'steps_taken.*' => ['string', 'max:500'],
            'resolution_type' => ['required', Rule::enum(ResolutionType::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'root_cause.min' => 'Please provide at least 10 characters for the root cause.',
            'steps_taken.max' => 'You may record a maximum of 10 resolution steps.',
        ];
    }
}
