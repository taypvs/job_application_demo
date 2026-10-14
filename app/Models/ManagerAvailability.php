<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['manager_id', 'weekday', 'start_time', 'end_time'])]
class ManagerAvailability extends Model
{
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }
}
