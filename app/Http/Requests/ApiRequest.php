<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for every API form request. Authorization lives in Policies, so authorize() is
 * always true here; attribute names come from lang/{locale}/attributes.php.
 */
abstract class ApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = trans('attributes');

        return is_array($attributes) ? array_filter($attributes, 'is_string') : [];
    }
}
