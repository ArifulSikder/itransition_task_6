<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCircuitPresenceRequest extends FormRequest
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
            'cursor_x' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:'.$max],
            'cursor_y' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:'.$max],
        ];
    }
}
