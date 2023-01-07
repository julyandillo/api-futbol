<?php

namespace App\Config;

enum Posicion: string
{
    case PORTERO = 'POR';
    case DEFENSA = 'DEF';
    case MEDIOCENTRO = 'MED';
    case DELANTERO = 'DEL';
}
