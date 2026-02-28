<?php

namespace App\Http\Requests\Assets;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('asset'));
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $asset = $this->route('asset');
        $actor = $this->user();

        return [
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
                function ($attribute, $value, $fail) use ($asset, $actor): void {
                    $assignee = User::find($value);

                    if (! $assignee) {
                        return;
                    }

                    if (! $assignee->hasAnyRole(['icto', 'aicto'])) {
                        $fail('The assigned user must be an ICTO or AICTO officer.');

                        return;
                    }

                    // RICTO can only assign to officers in the same region as the asset.
                    if ($actor->hasRole('ricto')) {
                        $assetRegionId = $asset->county()->value('region_id');

                        if ($assignee->region_id !== $assetRegionId) {
                            $fail('The assigned user must be in the same region as the asset.');
                        }
                    }
                },
            ],
        ];
    }
}
