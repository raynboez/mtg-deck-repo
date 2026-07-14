<?php

namespace App\Enums;

enum SubfactionXenos: string
{
    case Aeldari = "Aeldari";
    case Drukhari = "Drukhari";
    case GenestealerCults = "Genestealer Cults";
    case LeaguesOfVotann = "Leagues of Votann";
    case Necrons = "Necrons";
    case Orks = "Orks";
    case TauEmpire = "Tau Empire";
    case Tyranids = "Tyranids";

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    public static function tryFromName(string $name): ?self
        {
            foreach (self::cases() as $case) {
                if ($case->name === $name) {
                    return $case;
                }
            }
            return null;
        }
}
