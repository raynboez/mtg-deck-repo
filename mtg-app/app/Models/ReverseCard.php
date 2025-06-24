<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ReverseCard
 * 
 * @property int $card_id
 * @property int $face_card_id
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
 * @property Card $card
 *
 * @package App\Models
 */
class ReverseCard extends Model
{
	protected $table = 'reverse_cards';
	protected $primaryKey = 'card_id';
	public $timestamps = false;

	protected $casts = [
		'face_card_id' => 'int',
		'cmc' => 'float',
		'is_gamechanger' => 'bool'
	];

	protected $fillable = [
		'face_card_id',
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

	public function card()
	{
		return $this->belongsTo(Card::class, 'face_card_id');
	}
}
