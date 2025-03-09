<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

trait JsonParserRequest
{
    private array $jsonContent = [];

    /**
     * @throws \JsonException
     */
    public function parseJsonRequest(Request $request): void
    {
        $this->jsonContent = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}