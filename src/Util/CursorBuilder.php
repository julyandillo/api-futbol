<?php

namespace App\Util;

use App\ApiCursor\ApiCursor;
use App\Exception\ApiCursorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait CursorBuilder
{
    /**
     * @throws ApiCursorException
     */
    public function getCursorForRequest(Request $request, array $allowParams, string $defaultOrder): ApiCursor
    {
        $bag = $request->isMethod('GET') ? $request->query : $request->request;

        if ($bag->has('cursor')) {
            return ApiCursor::decode($bag->get('cursor'));
        }

        return $this->createCursorWithRequest($request, $allowParams, $defaultOrder);
    }

    /**
     * @throws ApiCursorException
     */
    public function createCursorWithRequest(Request $request, array $allowParams, string $defaultOrder = 'id'): ApiCursor
    {
        $cursor = new ApiCursor();

        if (!$request->query->has('order')) {
            $cursor->setOrderBy([$defaultOrder => 'ASC']);

        } else {
            if (!in_array(strtolower($request->query->get('order')), $allowParams, true)) {
                throw new ApiCursorException(
                    'Sólo esta permitida la ordenación por los campos: ' . implode(', ', $allowParams) . '.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $direction = strtoupper($request->query->get('direction', 'ASC'));

            if ($direction !== 'ASC' && $direction !== 'DESC') {
                throw new ApiCursorException('El orden debe ser \'ASC\' o \'DESC\'', Response::HTTP_BAD_REQUEST);
            }

            $cursor->setOrderBy([$request->query->get('order') => $direction]);
        }

        // TODO - permitir filtros, ej: capacidad > 50000

        return $cursor;
    }
}