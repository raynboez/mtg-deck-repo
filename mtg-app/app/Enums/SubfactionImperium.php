<?php

namespace App\Enums;

enum SubfactionImperium: string
{
    case AdeptaSororitas = "Adepta Sororitas";
    case AdeptusCustodes = "Adeptus Custodes";
    case AdeptusMechanicus = "Adeptus Mechanicus";
    case AdeptusTitanicus = "Adeptus Titanicus";
    case AgentsOfTheImperium = "Agents of the Imperium";
    case AstraMilitarum = "Astra Militarum";
    case GreyKnights = "Grey Knights";
    case ImperialKnights = "Imperial Knights";

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
