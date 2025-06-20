<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Card
 * 
 * @property int $card_id
 * @property string $card_name
 * @property string|null $mana_cost
 * @property float $cmc
 * @property string $type_line
 * @property string|null $oracle_text
 * @property string|null $power
 * @property string|null $toughness
 * @property string|null $colours
 * @property string|null $colour_identity
 * @property string|null $image_url
 * @property string|null $scryfall_uri
 * @property string|null $set
 * @property string|null $collector_number
 * @property bool|null $is_gamechanger
 * @property string|null $oracle_id
 * 
 * @property Collection|Deck[] $decks
 *
 * @package App\Models
 */
class Card extends Model
{
	protected $table = 'cards';
	protected $primaryKey = 'card_id';
	public $timestamps = false;

	protected $casts = [
		'cmc' => 'float',
		'is_gamechanger' => 'bool'
	];

	protected $fillable = [
		'card_name',
		'mana_cost',
		'cmc',
		'type_line',
		'oracle_text',
		'power',
		'toughness',
		'colours',
		'colour_identity',
		'image_url',
		'scryfall_uri',
		'set',
		'collector_number',
		'is_gamechanger',
		'oracle_id'
	];

	public function decks()
	{
		return $this->belongsToMany(Deck::class, 'deck_cards')
					->withPivot('is_commander', 'is_companion', 'is_main_deck', 'is_sideboard', 'quantity');
	}
}
