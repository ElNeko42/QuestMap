<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesGeoPoint;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRequest extends FormRequest
{
    use ResolvesGeoPoint;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->geoRules() + [
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // 10 MB
        ];
    }
}
