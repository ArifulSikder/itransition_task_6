<?php

namespace App\Http\Requests;

use App\Models\Circuit;
use App\Models\CircuitNode;
use App\Models\CircuitWire;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCircuitWireRequest extends FormRequest
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
        return [
            'uuid' => ['required', 'uuid', Rule::unique('circuit_wires', 'uuid')],
            'from_node_uuid' => ['required', 'uuid'],
            'to_node_uuid' => ['required', 'uuid', 'different:from_node_uuid'],
            'from_port' => ['required', 'integer', 'min:0', 'max:3'],
            'to_port' => ['required', 'integer', 'min:0', 'max:3'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $circuit = $this->route('circuit');

                if (! $circuit instanceof Circuit) {
                    return;
                }

                $from = $circuit->nodes()->where('uuid', $this->input('from_node_uuid'))->first();
                $to = $circuit->nodes()->where('uuid', $this->input('to_node_uuid'))->first();

                if ($from === null) {
                    $validator->errors()->add('from_node_uuid', 'The source gate was not found on this circuit.');

                    return;
                }

                if ($to === null) {
                    $validator->errors()->add('to_node_uuid', 'The destination gate was not found on this circuit.');

                    return;
                }

                if ($this->integer('from_port') >= $from->type->outputCount()) {
                    $validator->errors()->add('from_port', 'That gate has no output pin there.');
                }

                if ($this->integer('to_port') >= $to->type->inputCount()) {
                    $validator->errors()->add('to_port', 'That gate has no input pin there.');
                }

                $occupied = CircuitWire::query()
                    ->where('circuit_id', $circuit->id)
                    ->where('to_node_id', $to->id)
                    ->where('to_port', $this->integer('to_port'))
                    ->exists();

                if ($occupied) {
                    $validator->errors()->add('to_port', 'That input pin is already wired.');
                }

                $this->merge([
                    'from_node_id' => $from->id,
                    'to_node_id' => $to->id,
                ]);
            },
        ];
    }

    public function fromNode(): CircuitNode
    {
        $circuit = $this->route('circuit');
        assert($circuit instanceof Circuit);

        return $circuit->nodes()->where('uuid', $this->string('from_node_uuid')->toString())->firstOrFail();
    }

    public function toNode(): CircuitNode
    {
        $circuit = $this->route('circuit');
        assert($circuit instanceof Circuit);

        return $circuit->nodes()->where('uuid', $this->string('to_node_uuid')->toString())->firstOrFail();
    }
}
