<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlaConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('sla_configuration'));
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'acknowledge_within_hrs' => ['required', 'integer', 'min:1'],
            'resolve_within_hrs' => ['required', 'integer', 'min:1', 'gte:acknowledge_within_hrs'],
            'effective_from' => ['required', 'date'],
        ];
    }
}
