<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Season
 * 
 * @property int $id
 * @property string $name
 * @property Carbon|null $date_started
 * @property Carbon|null $date_ended
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|BannedCard[] $banned_cards
 *
 * @package App\Models
 */
class Season extends Model
{
	protected $table = 'seasons';

	protected $casts = [
		'date_started' => 'datetime',
		'date_ended' => 'datetime'
	];

	protected $fillable = [
		'name',
		'date_started',
		'date_ended'
	];

	public function banned_cards()
	{
		return $this->hasMany(BannedCard::class);
	}
}
