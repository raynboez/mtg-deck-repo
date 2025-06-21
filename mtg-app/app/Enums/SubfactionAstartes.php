<?php

namespace App\Enums;

enum SubfactionAstartes: string
{
    case BlackTemplars = "Black Templars";
    case BloodAngels  = "Blood Angels";
    case DarkAngels = "Dark Angels";
    case Deathwatch = "Deathwatch";
    case ImperialFists = "Imperial Fists";
    case IronHands = "Iron Hands";
    case RavenGuard = "Raven Guard";
    case Salamanders = "Salamanders";
    case SpaceMarines = "Space Marines";
    case SpaceWolves = "Space Wolves";
    case Ultramarines = "Ultramarines";
    case WhiteScars = "White Scars";

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

}
