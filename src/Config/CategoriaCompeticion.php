<?php

namespace App\Config;

enum CategoriaCompeticion: string
{
    case Liga = 'LIGA';
    case CopaDelRey = 'COPA';
    case ChampionsLeague = 'CHAMPIONS_LEAGUE';
    case EuropeLeague = 'EUROPA_LEAGUE';
    case Mundial = 'MUNDIAL';
    case Eurocopa = 'EUROCOPA';
    case ChampionsLeagueLegacy = 'CHAMPIONS_LEAGUE_LEGACY';
    case EuropeLeagueLegacy = 'EUROPA_LEAGUE_LEGACY';

    public function nameForSelect(): string
    {
        return match ($this) {
            self::EuropeLeague => 'UEFA Europe League',
            self::ChampionsLeague => 'UEFA Champions League',
            self::CopaDelRey => 'Copa del Rey',
            self::Liga => 'Liga',
            self::Mundial => 'Mundial',
            self::Eurocopa => 'Eurocopa',
            self::ChampionsLeagueLegacy => 'Champions League (antiguo formato)',
            self::EuropeLeagueLegacy => 'Europe League (antiguo formato)',
        };
    }
}
