<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Match
 * 
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Matches extends Model
{
	protected $table = 'matches';

	protected $casts = [
        'played_at' => 'datetime',
    ];

    public function participants()
    {
        return $this->hasMany(MatchParticipant::class);
    }

    public function winners()
    {
        return $this->participants()->where('is_winner', true);
    }
}
