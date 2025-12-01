<?php

namespace App\ApiCursor;

use App\Exception\ApiCursorException;
use Doctrine\ORM\Query\Parameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCursorBuilder
{
    const string CURSOR_PARAMETER_NAME = 'next';

    private array $allowFieldsOrder = [];

    private array $allowFieldsFilter = [];

    /**
     * @throws ApiCursorException
     */
    public function buildCursorWithRequest(Request $request): ApiCursor
    {
        if ($request->query->has(self::CURSOR_PARAMETER_NAME)) {
            return $this->decode($request->query->get(self::CURSOR_PARAMETER_NAME));
        }

        return $this->createCursorWithRequest($request);
    }

    /**
     * @throws ApiCursorException
     */
    private function decode(string $encodedCursor): ApiCursor
    {
        $rawCursor = json_decode(base64_decode($encodedCursor), true);

        if (!isset($rawCursor['last_id'])) {
            throw new ApiCursorException('Cursor last ID not set');
        }

        return new ApiCursor(
            $rawCursor['last_id'],
            (int)$rawCursor['offset'],
            (int)$rawCursor['limit'],
            $rawCursor['filters'] ?? [],
            $rawCursor['order_by'] ?? [],
            isset($rawCursor['total_rows']) ? (int)$rawCursor['total_rows'] : 0,
        );
    }

    public function setAllowFieldOrders(array $allowFieldOrders): static
    {
        $this->allowFieldsOrder = $allowFieldOrders;
        return $this;
    }

    public function setAllowFieldFilters(array $allowFieldFilters): static
    {
        $this->allowFieldsFilter = $allowFieldFilters;
        return $this;
    }

    /**
     * @throws ApiCursorException
     */
    private function createCursorWithRequest(Request $request): ApiCursor
    {
        $cursor = new ApiCursor();

        if ($request->query->has('order')) {
            if (!in_array(strtolower($request->query->get('order')), $this->allowFieldsOrder, true)) {
                throw new ApiCursorException(
                    'Sólo está permitida la ordenación por: ' . implode(', ', $this->allowFieldsOrder) . '.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $direction = strtoupper($request->query->get('direction', 'ASC'));

            if ($direction !== 'ASC' && $direction !== 'DESC') {
                throw new ApiCursorException('El orden debe ser \'ASC\' o \'DESC\'', Response::HTTP_BAD_REQUEST);
            }

            $cursor->setOrderBy([$request->query->get('order') => $direction]);
        }

        if ($request->query->has('limit')) {
            $cursor->setLimit($request->query->get('limit'));
        }

        if ($request->query->has('offset')) {
            $cursor->setOffset($request->query->get('offset'));
        }

        if ($request->query->has('total_rows')) {
            $cursor->setTotalRows($request->query->get('total_rows'));
        }

        foreach ($this->allowFieldsFilter as $field) {
            if (!$request->query->has($field)) {
                continue;
            }

            $operator = match (true) {
                str_ends_with($field, '_max') => '<=',
                str_ends_with($field, '_min') => '>=',
                default => '=',
            };

            $fieldSanitized = str_replace(['_max', '_min'], '', $field);

            $cursor->addParameter(new Parameter($fieldSanitized, $request->query->get($field)));
            $cursor->addFilter("$fieldSanitized $operator :$fieldSanitized");

            // para poder codificar los filtros para la siguiente petición tal y como vienen en la petición original
            $cursor->addRawFilter($field, $request->query->get($field));
        }

        return $cursor;
    }
}