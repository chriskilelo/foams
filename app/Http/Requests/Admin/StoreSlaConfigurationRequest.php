<?php

namespace App\Http\Requests\Admin;

use App\Enums\IssueSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSlaConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\SlaConfiguration::class);
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'severity' => ['required', Rule::enum(IssueSeverity::class)],
            'acknowledge_within_hrs' => ['required', 'integer', 'min:1'],
            'resolve_within_hrs' => ['required', 'integer', 'min:1', 'gte:acknowledge_within_hrs'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
