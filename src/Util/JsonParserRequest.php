<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

trait JsonParserRequest
{
    private array $contenidoPeticion = [];

    public function parseaContenidoPeticionJson(Request $request): void
    {
        $this->contenidoPeticion = json_decode($request->getContent(), true);
    }
}