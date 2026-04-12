<?php

namespace App\Config;

use Symfony\Component\Translation\TranslatableMessage;

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

    public function nameForSelect(): TranslatableMessage
    {
        $message = match ($this) {
            self::EuropeLeague => 'UEFA Europe League',
            self::ChampionsLeague => 'UEFA Champions League',
            self::CopaDelRey => 'Copa del Rey',
            self::Liga => 'League',
            self::Mundial => 'World Cup',
            self::Eurocopa => 'Eurocup',
            self::ChampionsLeagueLegacy => 'UEFA Champions League (old format)',
            self::EuropeLeagueLegacy => 'UEFA Europe League (old fortmat)',
        };

        return new TranslatableMessage($message, [], 'forms');
    }
}
