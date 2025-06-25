<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Deck
 * 
 * @property int $deck_id
 * @property int|null $user_id
 * @property string $deck_name
 * @property string|null $description
 * @property string|null $colour_identity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool|null $is_public
 * @property int|null $power_level
 * @property bool|null $is_paper
 * 
 * @property User|null $user
 * @property Collection|Card[] $cards
 *
 * @package App\Models
 */
class Deck extends Model
{
	protected $table = 'decks';
	protected $primaryKey = 'deck_id';

	protected $casts = [
		'user_id' => 'int',
		'is_public' => 'bool',
		'power_level' => 'int',
		'is_paper' => 'bool',
		'export_text' => 'string'
	];

	protected $fillable = [
		'user_id',
		'deck_name',
		'description',
		'colour_identity',
		'is_public',
		'power_level',
		'is_paper',
		'export_text'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function cards()
	{
		return $this->belongsToMany(Card::class, 'deck_cards', 'deck_id', 'card_id')
					->withPivot('is_commander', 'is_companion', 'is_main_deck', 'is_sideboard', 'quantity');
	}
}
