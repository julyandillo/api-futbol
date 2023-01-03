<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

trait CompruebaParametrosTrait
{
    public function peticionConParametrosObligatorios(array $parametrosObligatorios, Request $request): bool
    {
        return empty(array_diff($parametrosObligatorios, array_keys(json_decode($request->getContent(), true))));
    }
}