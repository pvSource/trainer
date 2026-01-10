<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeasurementUser extends Model
{
    use SoftDeletes;

    protected $table = 'measurement_users';

    protected $fillable = [
        'measurement_id',
        'user_id',
        'value',
        'measure_at',
    ];

    protected function casts(): array
    {
        return [
            'measurement_id' => 'integer',
            'user_id' => 'integer',
            'value' => 'decimal:2',
            'measure_at' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
