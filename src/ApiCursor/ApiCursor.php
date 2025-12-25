<?php

namespace App\ApiCursor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

class ApiCursor implements \JsonSerializable
{
    const int MAX_VALUES = 50;

    const string LAST_PAGE = 'last_page';

    private array $rawFilters = [];
    private array $filters = [];

    private string $sql = '';

    public function __construct(
        private int                      $lastID = 0,
        private int                      $offset = 0,
        private int                      $limit = 10,
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
        $fields = [
            'last_id' => $this->lastID,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'filters' => $this->rawFilters,
            'order_by' => $this->orderBy,
            'total_rows' => $this->totalRows,
        ];

        return empty($this->sql) ? $fields : array_merge($fields, ['sql' => $this->sql]);
    }

    public function getFilters(): array
    {
        return $this->filters;
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

    public function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    public function addSqlFilter(string $field, mixed $value): static
    {
        $operator = match (true) {
            str_ends_with($field, '_max') => '<=',
            str_ends_with($field, '_min') => '>=',
            default => '=',
        };

        $fieldSanitized = str_replace(['_max', '_min'], '', $field);

        if (str_contains($fieldSanitized, '_')) {
            $words = explode('_', $fieldSanitized);
            $fieldSanitized = array_shift($words);
            $fieldSanitized .= implode('', array_map('ucfirst', $words));
        }

        $this->addParameter(new Parameter($fieldSanitized, $value));
        $this->filters[] = "$fieldSanitized $operator :$fieldSanitized";

        // para poder codificar los filtros para la siguiente petición tal y como vienen en la petición original
        $this->addRawFilter($field, $value);

        return $this;
    }
}