<?php

namespace App\Http\Requests\File;

use App\DTOs\Filters\FileFilters;
use App\Enums\FileType;
use App\Http\Requests\ApiRequest;
use App\Http\Requests\Concerns\ParsesListParams;
use Illuminate\Validation\Rule;

class IndexFileRequest extends ApiRequest
{
    use ParsesListParams;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->listRules(),
            'project_id' => ['sometimes', 'nullable', 'integer'],
            'type' => ['sometimes', 'nullable', Rule::enum(FileType::class)],
        ];
    }

    public function toFilters(): FileFilters
    {
        return new FileFilters(
            projectId: $this->intOrNull('project_id'),
            type: $this->stringOrNull('type'),
            search: $this->stringOrNull('search'),
            perPage: $this->perPageParam(24),
        );
    }
}
