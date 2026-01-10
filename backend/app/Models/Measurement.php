<?php

namespace App\Models;

use App\Models\MeasurementMuscle;
use App\Models\MeasurementUser;
use App\Models\Muscle;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Measurement extends Model {
    use SoftDeletes;

    protected $table = 'measurements';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'code',
        'unit',
        'description',
    ];

    protected function casts()
    {
        return [
            'name' => 'string',
            'code' => 'string',
            'unit' => 'string',
            'description' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime'
        ];
    }

    /**
     * Связь с мышцами (many-to-many)
     */
    public function muscles(): BelongsToMany
    {
        return $this->belongsToMany(Muscle::class, MeasurementMuscle::class, 'measurement_id', 'muscle_id')->withTimestamps();
    }

    /**
     * Связь со всеми записями замеров пользователей (has-many)
     */
    public function measurementUsers(): HasMany
    {
        return $this->hasMany(MeasurementUser::class, 'measurement_id');
    }

    /**
     * Получить все замеры для конкретного пользователя
     */
    public function userMeasurements(int $userId): HasMany
    {
        return $this->hasMany(MeasurementUser::class, 'measurement_id')
            ->where('user_id', $userId);
    }
}
