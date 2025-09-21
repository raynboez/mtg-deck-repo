<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MatchParticipant
 * 
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class MatchParticipant extends Model
{
	protected $table = 'match_participants';
    public $timestamps = false;
	protected $fillable = [
        'match_id', 'user_id', 'deck_id', 'is_winner', 
        'starting_life', 'final_life', 'turn_order',
		'turn_lost', 'order_lost', 'first_blood', 'motm'
    ];

    public function match()
    {
        return $this->belongsTo(Matches::class, 'match_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deck()
    {
        return $this->belongsTo(Deck::class, "deck_id");
    }
}
