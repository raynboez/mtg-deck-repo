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
    protected $primaryKey = 'match_id';
    protected $fillable = [
        'played_at','bracket',
        'match_type', 'notes', 'number_of_players', 'total_turns'
    ];

	protected $casts = [
        'played_at' => 'datetime',
    ];

    public function participants()
    {
        return $this->hasMany(MatchParticipant::class, 'match_id');
    }

    public function winners()
    {
        return $this->participants()->where('is_winner', true);
    }
}
