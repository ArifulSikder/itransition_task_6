<?php

namespace App\Http\Requests;

use App\Enums\GateType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCircuitNodeRequest extends FormRequest
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
            'uuid' => ['required', 'uuid', Rule::unique('circuit_nodes', 'uuid')],
            'type' => ['required', Rule::enum(GateType::class)],
            'x' => ['required', 'integer', 'min:0', 'max:'.$max],
            'y' => ['required', 'integer', 'min:0', 'max:'.$max],
            'label' => ['sometimes', 'nullable', 'string', 'max:24'],
            'value' => ['sometimes', 'boolean'],
        ];
    }
}
