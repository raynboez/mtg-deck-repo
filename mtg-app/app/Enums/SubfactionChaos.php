<?php

namespace App\Enums;

enum SubfactionChaos: string
{
    case ChaosDaemons = "Chaos Daemons";
    case ChaosKnights = "Chaos Knights";
    case ChaosSpaceMarines = "Chaos Space Marines";
    case DeathGuard = "Death Guard";
    case EmperorsChildren = "Emperors Children";
    case ThousandSons = "Thousand Sons";
    case TitanticusTraitoris =  "Titanicus Traitoris";
    case WorldEaters = "World Eaters";
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
