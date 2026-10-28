<?php

namespace App\Http\Requests\Concerns;

/**
 * Helpers for list endpoints: comma lists, "-field" sorting, nullable ints/bools.
 */
trait ParsesListParams
{
    /**
     * @return list<string>
     */
    protected function csv(string $key): array
    {
        $value = $this->query($key);
        if (! is_string($value) || $value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $v): bool => $v !== ''));
    }

    protected function intOrNull(string $key): ?int
    {
        $value = $this->query($key);

        return is_numeric($value) ? (int) $value : null;
    }

    protected function stringOrNull(string $key): ?string
    {
        $value = $this->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    protected function flag(string $key): bool
    {
        return filter_var($this->query($key), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array{0: string, 1: 'asc'|'desc'}
     */
    protected function sortParam(string $default, string $defaultDirection = 'asc'): array
    {
        $sort = $this->stringOrNull('sort');
        if ($sort === null) {
            return [$default, $defaultDirection === 'desc' ? 'desc' : 'asc'];
        }

        return str_starts_with($sort, '-') ? [substr($sort, 1), 'desc'] : [$sort, 'asc'];
    }

    protected function perPageParam(int $default): int
    {
        return max(1, min(100, $this->intOrNull('per_page') ?? $default));
    }

    /**
     * @return array<string, mixed>
     */
    protected function listRules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'sort' => ['sometimes', 'nullable', 'string', 'max:40'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
