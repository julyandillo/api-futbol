<?php

namespace App\ApiCursor;

use App\Exception\ApiCursorException;

class ApiCursor implements \JsonSerializable
{
    private int $lastID;

    public function __construct(
        private int|string|null $lastValue = null,
        private array           $filters = [],
        private array           $orderBy = [])
    {
        $this->lastID = 0;
    }


    public function encode(): string
    {
        return base64_encode(json_encode($this));
    }

    /**
     * @throws ApiCursorException
     */
    public static function decode(string $encodedCursor): ApiCursor
    {
        $rawCursor = json_decode(base64_decode($encodedCursor), true);

        if (!isset($rawCursor['last_item'])) {
            throw new ApiCursorException('Cursor last item not set');
        }

        return new self($rawCursor['last_item'], $rawCursor['filters'] ?? [], $rawCursor['order_by'] ?? []);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'last_item' => $this->lastValue,
            'filters' => $this->filters,
            'order_by' => $this->orderBy,
        ];
    }

    public function setLastValue(int|string $lastValue): void
    {
        $this->lastValue = $lastValue;

    }

    public function getLastValue(): int|string|null
    {
        return $this->lastValue;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): ApiCursor
    {
        $this->filters = $filters;
        return $this;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function setOrderBy(array $orderBy): ApiCursor
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function getLastID(): int
    {
        return $this->lastID;
    }

    public function setLastID(int $lastID): ApiCursor
    {
        $this->lastID = $lastID;
        return $this;
    }

}