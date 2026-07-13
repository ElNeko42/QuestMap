<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesGeoPoint;
use Illuminate\Foundation\Http\FormRequest;

class CheckinRequest extends FormRequest
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
        return $this->geoRules();
    }
}
