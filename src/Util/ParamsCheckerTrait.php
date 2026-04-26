<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\Request;

trait ParamsCheckerTrait
{
    private array $missingMandatoryParams = [];

    /**
     * @throws \JsonException
     */
    public function checkIfRequestHasMandatoryParams(array $mandatoryParams, Request $request): bool
    {

    }


}