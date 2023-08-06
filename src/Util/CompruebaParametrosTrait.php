<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait CompruebaParametrosTrait
{
    private array $parametrosInexistentes = [];

    public function peticionConParametrosObligatorios(array $parametrosObligatorios, Request $request): bool
    {
        $parametrosPeticion = stristr($request->headers->get('Content-type'), 'application/json') !== false
            ? array_keys(json_decode($request->getContent(), true))
            : $request->request->keys();

        $this->parametrosInexistentes = array_diff($parametrosObligatorios, $parametrosPeticion);

        return empty($this->parametrosInexistentes);
    }

    public function getParametrosObligatoriosInexistentes(): array
    {
        return $this->parametrosInexistentes;
    }

    public function stringConParametrosInexistentes(string $separador = ','): string
    {
        return implode($separador, $this->parametrosInexistentes);
    }

    public function creaRespuestaConParametrosObligatoriosInexistentes(string $formato = null): JsonResponse
    {
        return new JsonResponse([
            'msg' => sprintf($formato ?? 'No se puede realizar la petición, faltan parámetros obligatorios: [%s]',
                $this->stringConParametrosInexistentes())
        ], Response::HTTP_BAD_REQUEST);
    }
}