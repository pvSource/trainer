<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeasurementMuscle extends Model
{
    use SoftDeletes;

    protected $table = 'measurement_muscles';

    protected $fillable = [
        'muscle_id',
        'measurement_id',
    ];

    protected function casts(): array
    {
        return [
            'measurement_id' => 'integer',
            'muscle_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    public function muscle(): BelongsTo
    {
        return $this->belongsTo(Muscle::class);
    }
}
