<?php

namespace App\Enums;

enum Faction: string
{
    case Chaos = 'Chaos';
    case Imperium = 'Imperium';
    case Astartes = 'Astartes';
    case Xenos = 'Xenos';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
