<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WarhammerMatchParticipant
 * 
 * @property int $participant_id
 * @property int $match_id
 * @property int $user_id
 * @property int $army_id
 * @property bool $is_winner
 * @property int $victory_points
 * @property int|null $primary_points
 * @property int|null $secondary_points
 * @property int|null $tertiary_points
 * 
 * @property Army $army
 * @property WarhammerMatch $warhammer_match
 * @property User $user
 *
 * @package App\Models
 */
class WarhammerMatchParticipant extends Model
{
	protected $table = 'warhammer_match_participants';
	protected $primaryKey = 'participant_id';
	public $timestamps = false;

	protected $casts = [
		'match_id' => 'int',
		'user_id' => 'int',
		'army_id' => 'int',
		'is_winner' => 'bool',
		'victory_points' => 'int',
		'primary_points' => 'int',
		'secondary_points' => 'int',
		'tertiary_points' => 'int'
	];

	protected $fillable = [
		'match_id',
		'user_id',
		'army_id',
		'is_winner',
		'victory_points',
		'primary_points',
		'secondary_points',
		'tertiary_points',
		'primary_objective',
		'secondary_objective'
	];

	public function army()
	{
		return $this->belongsTo(Army::class, 'army_id');
	}

	public function warhammer_match()
	{
		return $this->belongsTo(WarhammerMatch::class, 'match_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
}
