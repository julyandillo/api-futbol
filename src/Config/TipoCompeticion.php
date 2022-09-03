<?php

namespace App\Config;

enum TipoCompeticion: string
{
    case Liga = 'LIGA';
    case TorneoConLiguilla = 'TORNEO_CON_LIGA';
    case TorneoSoloConEliminatorias = 'TORNEO_KO';
}
