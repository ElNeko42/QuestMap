<?php

declare(strict_types=1);

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

class ValidationCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // HMAC middleware already authenticated the caller.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'valid' => ['required', 'boolean'],
            'confidence' => ['required', 'numeric', 'between:0,1'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
