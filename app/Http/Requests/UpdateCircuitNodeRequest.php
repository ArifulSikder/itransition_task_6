<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCircuitNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $max = (int) config('circuits.canvas_max');

        return [
            'x' => ['sometimes', 'integer', 'min:0', 'max:'.$max],
            'y' => ['sometimes', 'integer', 'min:0', 'max:'.$max],
            'label' => ['sometimes', 'nullable', 'string', 'max:24'],
            'value' => ['sometimes', 'boolean'],
        ];
    }
}
