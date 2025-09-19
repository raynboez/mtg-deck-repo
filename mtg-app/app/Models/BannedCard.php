<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BannedCard
 * 
 * @property int $id
 * @property int $season_id
 * @property int $card_id
 * @property int $banned_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Card $card
 * @property Season $season
 *
 * @package App\Models
 */
class BannedCard extends Model
{
	protected $table = 'banned_cards';

	protected $casts = [
		'season_id' => 'int',
		'card_id' => 'int',
		'banned_by' => 'int'
	];

	protected $fillable = [
		'season_id',
		'card_id',
		'banned_by',
		'notes'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'banned_by');
	}

	public function card()
	{
		return $this->belongsTo(Card::class);
	}

	public function season()
	{
		return $this->belongsTo(Season::class);
	}
}
