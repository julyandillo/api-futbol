<?php

namespace App\Policy;

use Symfony\Component\HttpFoundation\Request;

abstract class ApiPolicy implements RequestPolicyInterface
{
    public const string MANDATORY_PARAMS = 'mandatory_params';

    abstract public function apply(array $context, Request $request): void;
}
