<?php

namespace App\Http\Requests\Issues;

use App\Enums\IssueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(IssueStatus::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
