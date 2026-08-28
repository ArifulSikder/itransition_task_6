<?php

namespace App\Actions;

use App\Models\Circuit;
use Illuminate\Support\Facades\DB;

class BumpCircuitRevision
{
    public function handle(Circuit $circuit): Circuit
    {
        Circuit::query()->whereKey($circuit->id)->update([
            'revision' => DB::raw('revision + 1'),
            'updated_at' => now(),
        ]);

        return $circuit->refresh();
    }
}
