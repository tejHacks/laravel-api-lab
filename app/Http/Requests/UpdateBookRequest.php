<?php

namespace App\Http\Requests;

/**
 * PATCH: clients send only the fields they want to change.
 * 'sometimes' = validate a field only when it is present.
 */
class UpdateBookRequest extends StoreBookRequest
{
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules) => ['sometimes', ...$rules])
            ->all();
    }
}
