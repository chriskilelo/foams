<?php

namespace App\Http\Requests\Assets;

use App\Enums\AssetLogStatus;
use App\Models\AssetStatusLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStatusLogRequest extends FormRequest
{
    /**
     * Authorization is handled by the controller via authorize().
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Determine server-side whether this submission is an amendment so that
     * the required_if rule for amendment_reason works correctly regardless of
     * what the client sends.
     */
    protected function prepareForValidation(): void
    {
        /** @var \App\Models\Asset $asset */
        $asset = $this->route('asset');

        $isAmendment = AssetStatusLog::query()
            ->where('asset_id', $asset->id)
            ->where('user_id', $this->user()->id)
            ->whereDate('logged_date', now()->toDateString())
            ->exists();

        $this->merge(['is_amendment' => $isAmendment]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(AssetLogStatus::class)],
            'remarks' => ['nullable', 'string', 'max:500'],
            'observed_at' => ['nullable', 'date_format:H:i'],
            'throughput_mbps' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'latitude' => ['nullable', 'numeric', 'min:-90', 'max:90'],
            'longitude' => ['nullable', 'numeric', 'min:-180', 'max:180'],
            'amendment_reason' => ['required_if:is_amendment,true', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amendment_reason.required_if' => 'An amendment reason is required when updating a log that already exists for today.',
        ];
    }
}
