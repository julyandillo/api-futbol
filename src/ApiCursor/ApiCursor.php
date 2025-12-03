<?php

namespace App\ApiCursor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

class ApiCursor implements \JsonSerializable
{
    const int MAX_VALUES = 50;

    const string LAST_PAGE = 'last_page';

    private array $filters = [];

    public function __construct(
        private int                      $lastID = 0,
        private int                      $offset = 0,
        private int                      $limit = 10,
        private array                    $rawFilters = [],
        private array                    $orderBy = ['id' => 'ASC'],
        private int                      $totalRows = 0,
        private readonly ArrayCollection $parameters = new ArrayCollection())
    {
    }

    public function encode(): string
    {
        return base64_encode(json_encode($this));
    }

    public function jsonSerialize(): mixed
    {
        return [
            'last_id' => $this->lastID,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'filters' => $this->rawFilters,
            'order_by' => $this->orderBy,
            'total_rows' => $this->totalRows,
        ];
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

    public function hasFilters(): bool
    {
        return !empty($this->filters);
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

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): ApiCursor
    {
        $this->offset = $offset;
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): ApiCursor
    {
        $this->limit = min($limit, self::MAX_VALUES);
        return $this;
    }

    public function useDefaultOrder(): bool
    {
        return empty($this->orderBy) || empty(array_diff_assoc($this->orderBy, ['id' => 'ASC']));
    }

    public function setTotalRows(int $totalRows): ApiCursor
    {
        $this->totalRows = $totalRows;
        return $this;
    }

    public function hasMorePages(): bool
    {
        return $this->offset + $this->limit < $this->totalRows;
    }

    public function getNextPage(): string
    {
        if (!$this->hasMorePages()) {
            return self::LAST_PAGE;
        }

        $this->offset += $this->limit;

        return $this->encode();
    }

    public function isFirstFetch(): bool
    {
        return $this->totalRows === 0;
    }

    public function addFilter(string $string): static
    {
        $this->filters[] = $string;
        return $this;
    }

    public function getParameters(): ArrayCollection
    {
        return $this->parameters;
    }

    public function addParameter(Parameter $parameter): static
    {
        $this->parameters->add($parameter);
        return $this;
    }

    public function addRawFilter(string $filter, mixed $value): static
    {
        $this->rawFilters[$filter] = $value;
        return $this;
    }
}