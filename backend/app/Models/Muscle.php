<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Muscle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'level',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'name' => 'string',
            'code' => 'string',
            'level' => 'integer',
            'description' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Родительская мышца
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Muscle::class, 'parent_id');
    }

    // Дочерние мышцы
    public function children(): HasMany
    {
        return $this->hasMany(Muscle::class, 'parent_id');
    }

    // Связь с упражнениями
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'exercises_muscles')
            ->using(ExerciseMuscle::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
