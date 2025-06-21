<?php

namespace App\Enums;

enum GameMode: string
{
    case wh40k = "Warhammer 40k";
    case killteam = "Killteam";

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
