<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

trait CompruebaParametrosTrait
{
    private array $parametrosFaltantes = [];

    public function peticionConParametrosObligatorios(array $parametrosObligatorios, Request $request): bool
    {
        $this->parametrosFaltantes = array_diff(
            $parametrosObligatorios,
            array_keys(json_decode($request->getContent(), true))
        );

        return empty($this->parametrosFaltantes);
    }

    public function getParametrosObligatoriosFaltantes(): array
    {
        return $this->parametrosFaltantes;
    }
}