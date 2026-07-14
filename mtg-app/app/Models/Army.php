<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\ArmyPhoto;
/**
 * Class Army
 * 
 * @property int $army_id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string $game_mode
 * @property int|null $points
 * @property string $faction
 * @property string|null $subfaction
 * @property string|null $army_link
 * @property string|null $list
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Collection|WarhammerMatchParticipant[] $warhammer_match_participants
 *
 * @package App\Models
 */
class Army extends Model
{
	protected $table = 'armies';
	protected $primaryKey = 'army_id';

	protected $casts = [
		'user_id' => 'int',
		'points' => 'int',
		'game_mode' => 'string',
		'faction' => 'string'
	];

	protected $fillable = [
		'user_id',
		'name',
		'description',
		'game_mode',
		'points',
		'faction',
		'subfaction',
		'army_link',
		'list'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public function warhammer_match_participants()
	{
		return $this->hasMany(WarhammerMatchParticipant::class);
	}

	public function photos()
    {
        return $this->hasMany(ArmyPhoto::class, 'army_id');
    }

    public function primaryPhoto()
    {
        return $this->hasOne(ArmyPhoto::class)->where('is_primary', true);
    }
}
