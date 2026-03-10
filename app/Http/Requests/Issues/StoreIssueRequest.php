<?php

namespace App\Http\Requests\Issues;

use App\Enums\IssueSeverity;
use App\Enums\ReporterCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
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
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'county_id' => ['required', 'integer', 'exists:counties,id'],
            'issue_type' => ['required', 'string', 'max:100'],
            'severity' => ['required', Rule::enum(IssueSeverity::class)],
            'reporter_category' => ['required', Rule::enum(ReporterCategory::class)],
            'reporter_name' => ['nullable', 'string', 'max:200'],
            'reporter_email' => ['nullable', 'email', 'max:200'],
            'reporter_phone' => ['nullable', 'string', 'max:20'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'workaround_applied' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.min' => 'Please provide at least 10 characters describing the issue.',
        ];
    }
}
