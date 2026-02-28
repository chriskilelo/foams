<?php

namespace App\Http\Requests\Admin;

use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('region'));
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var Region $region */
        $region = $this->route('region');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:regions,name,'.$region->id],
            'code' => ['required', 'string', 'max:10', 'unique:regions,code,'.$region->id],
            'is_active' => ['boolean'],
        ];
    }
}
