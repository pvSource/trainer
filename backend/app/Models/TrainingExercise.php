<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingExercise extends Model
{
    use SoftDeletes;

    protected $table = 'training_exercises';

    protected $fillable = [
        'training_id',
        'exercise_id',
        'approaches_count',
        'repetitions_count',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'training_id' => 'integer',
            'exercise_id' => 'integer',
            'approaches_count' => 'integer',
            'repetitions_count' => 'integer',
            'weight' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Тренировка
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    // Упражнение
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
