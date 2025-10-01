<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OverriddenCard
 * 
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $season_id
 * @property int $deck_id
 * @property int $base_card_id
 * @property int $override_card_id
 * 
 * @property Card $card
 * @property Deck $deck
 * @property Season $season
 *
 * @package App\Models
 */
class OverriddenCard extends Model
{
	protected $table = 'overridden_cards';

	protected $casts = [
		'season_id' => 'int',
		'deck_id' => 'int',
		'base_card_id' => 'int',
		'override_card_id' => 'int'
	];

	protected $fillable = [
		'season_id',
		'deck_id',
		'base_card_id',
		'override_card_id'
	];

	public function base_card()
	{
		return $this->belongsTo(Card::class, 'base_card_id');
	}

	public function override_card()
	{
		return $this->belongsTo(Card::class, 'override_card_id');
	}
	public function deck()
	{
		return $this->belongsTo(Deck::class);
	}

	public function season()
	{
		return $this->belongsTo(Season::class);
	}
}
