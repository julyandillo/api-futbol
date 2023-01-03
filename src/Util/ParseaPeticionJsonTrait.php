<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

trait ParseaPeticionJsonTrait
{
    private array $contenidoPeticion = [];

    public function parseaContenidoPeticionJson(Request $request): void
    {
        $this->contenidoPeticion = json_decode($request->getContent(), true);
    }
}