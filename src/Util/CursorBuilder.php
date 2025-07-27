<?php

namespace App\Util;

use App\ApiCursor\ApiCursor;
use App\Exception\ApiCursorException;
use Symfony\Component\HttpFoundation\Request;

trait CursorBuilder
{
    /**
     * @throws ApiCursorException
     */
    public function getCursorForRequest(Request $request): ApiCursor
    {
        $bag = $request->isMethod('GET') ? $request->query : $request->request;

        return $bag->has('cursor')
            ? ApiCursor::decode($bag->get('cursor'))
            : new ApiCursor();
    }
}