<?php

namespace App\Http\Requests\File;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UploadFilesRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var list<string> $extensions */
        $extensions = config('rgoli.uploads.extensions');

        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'files' => ['required', 'array', 'min:1', 'max:'.(int) config('rgoli.uploads.max_files')],
            'files.*' => ['required', 'file', 'max:'.(int) config('rgoli.uploads.max_kb'), 'extensions:'.implode(',', $extensions)],
        ];
    }
}
