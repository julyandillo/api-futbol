<?php

namespace App\Policy;

use Symfony\Component\HttpFoundation\Request;

interface RequestPolicyInterface
{
    public function apply(Request $request, array $options): void;
}