<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DeckCard
 * 
 * @property int $deck_id
 * @property int $card_id
 * @property bool|null $is_commander
 * @property bool|null $is_companion
 * @property bool $is_main_deck
 * @property bool|null $is_sideboard
 * @property int|null $quantity
 * 
 * @property Deck $deck
 * @property Card $card
 *
 * @package App\Models
 */
class DeckCard extends Model
{
	protected $table = 'deck_cards';

	protected $primaryKey = 'primKey';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'deck_id' => 'int',
		'card_id' => 'int',
		'is_commander' => 'bool',
		'is_companion' => 'bool',
		'is_main_deck' => 'bool',
		'is_sideboard' => 'bool',
		'quantity' => 'int'
	];

	protected $fillable = [
		'deck_id',
		'card_id',
		'is_commander',
		'is_companion',
		'is_main_deck',
		'is_sideboard',
		'quantity'
	];

	public function deck()
	{
		return $this->belongsTo(Deck::class);
	}

	public function card()
	{
		return $this->belongsTo(Card::class);
	}
}
