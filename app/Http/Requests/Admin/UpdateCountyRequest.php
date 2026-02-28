<?php

namespace App\Http\Requests\Admin;

use App\Models\County;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCountyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('county'));
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        /** @var County $county */
        $county = $this->route('county');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:counties,name,'.$county->id],
            'code' => ['required', 'string', 'max:10', 'unique:counties,code,'.$county->id],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ];
    }
}
