<?php

namespace App\Http\Requests\Issues;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignIssueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $assigneeId = $this->integer('assigned_to_user_id', 0) ?: null;

            if ($assigneeId === null) {
                return;
            }

            $assignee = User::query()
                ->where('id', $assigneeId)
                ->where('is_active', true)
                ->first();

            if (! $assignee) {
                $v->errors()->add('assigned_to_user_id', 'The selected officer is not active.');

                return;
            }

            if (! $assignee->hasAnyRole(['icto', 'noc'])) {
                $v->errors()->add('assigned_to_user_id', 'Issues may only be assigned to ICTOs or NOC Officers.');

                return;
            }

            $actor = $this->user();
            if ($actor->hasRole('ricto') && $actor->region_id) {
                if ($assignee->region_id !== $actor->region_id) {
                    $v->errors()->add('assigned_to_user_id', 'RICTOs may only assign issues to officers within their own region.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'assigned_to_user_id.exists' => 'The selected officer does not exist.',
        ];
    }
}
