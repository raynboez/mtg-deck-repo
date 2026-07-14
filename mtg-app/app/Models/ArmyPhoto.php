<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmyPhoto extends Model
{
    protected $fillable = [
        'army_id',
        'user_id',
        'photo_url',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function army(): BelongsTo
    {
        return $this->belongsTo(Army::class, "army_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}