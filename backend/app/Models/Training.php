<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'start_at',
        'finish_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'name' => 'string',
            'description' => 'string',
            'start_at' => 'datetime',
            'finish_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Связь с упражнениями (many-to-many через pivot)
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'training_exercises')
            ->withPivot('approaches_count', 'repetitions_count', 'weight')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
