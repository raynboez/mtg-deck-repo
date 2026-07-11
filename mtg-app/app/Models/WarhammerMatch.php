<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class WarhammerMatch
 * 
 * @property int $match_id
 * @property string $game_mode
 * @property int $number_of_players
 * @property string|null $notes
 * @property Carbon $played_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|WarhammerMatchParticipant[] $warhammer_match_participants
 *
 * @package App\Models
 */
class WarhammerMatch extends Model
{
	protected $table = 'warhammer_matches';
	protected $primaryKey = 'match_id';

	protected $casts = [
		'number_of_players' => 'int',
		'played_at' => 'datetime',
		'game_mode' => 'string',
	];

	protected $fillable = [
		'game_mode',
		'number_of_players',
		'notes',
		'played_at'
	];

	public function warhammer_match_participants()
	{
		return $this->hasMany(WarhammerMatchParticipant::class, 'match_id');
	}
}
