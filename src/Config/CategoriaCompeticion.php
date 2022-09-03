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
}
