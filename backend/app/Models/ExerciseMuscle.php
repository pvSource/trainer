<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExerciseMuscle extends Model
{
    use SoftDeletes;

    protected $table = 'exercises_muscles';

    protected $fillable = [
        'exercise_id',
        'muscle_id',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'exercise_id' => 'integer',
            'muscle_id' => 'integer',
            'is_primary' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Упражнение
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    // Мышца
    public function muscle(): BelongsTo
    {
        return $this->belongsTo(Muscle::class);
    }
}
