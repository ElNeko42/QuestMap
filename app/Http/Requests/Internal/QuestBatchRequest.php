<?php

declare(strict_types=1);

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

class QuestBatchRequest extends FormRequest
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
            'tenant_id' => ['required_without:tenant_slug', 'integer', 'exists:tenants,id'],
            'tenant_slug' => ['required_without:tenant_id', 'string', 'exists:tenants,slug'],

            'quests' => ['required', 'array', 'min:1', 'max:200'],
            'quests.*.title' => ['required', 'string', 'max:255'],
            'quests.*.description' => ['required', 'string'],
            'quests.*.category' => ['required', 'string', 'max:64'],
            'quests.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'quests.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'quests.*.validation_type' => ['required', 'in:photo_ai,checkin,data_input'],
            'quests.*.validation_prompt' => ['nullable', 'string'],
            'quests.*.xp_reward' => ['required', 'integer', 'min:0', 'max:100000'],
            'quests.*.geofence_radius_m' => ['sometimes', 'integer', 'min:5', 'max:5000'],
            'quests.*.creator_type' => ['sometimes', 'in:ai,business,admin'],
            'quests.*.category_max_completions' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'quests.*.max_completions' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'quests.*.starts_at' => ['sometimes', 'nullable', 'date'],
            'quests.*.expires_at' => ['sometimes', 'nullable', 'date'],
            'quests.*.dedup_hash' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}
