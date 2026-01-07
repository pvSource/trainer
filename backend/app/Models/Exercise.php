<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'description' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Связь с тренировками (many-to-many через pivot)
    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(Training::class, 'training_exercises')
            ->withPivot('approaches_count', 'repetitions_count', 'weight')
            ->withTimestamps();
    }

// Связь с мышцами (many-to-many через pivot)
    public function muscles(): BelongsToMany
    {
        return $this->belongsToMany(Muscle::class, 'exercises_muscles')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
