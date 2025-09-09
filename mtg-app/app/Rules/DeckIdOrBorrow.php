<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class DeckIdOrBorrow implements Rule
{
    public function passes($attribute, $value)
    {
        if ($value === 'borrow') {
            return true;
        }
        
        return DB::table('decks')->where('deck_id', $value)->exists();
    }

    public function message()
    {
        return 'The :attribute must be a valid deck ID or "borrow".';
    }
}