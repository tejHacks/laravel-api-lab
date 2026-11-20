<?php

namespace App\Http\Resources\V2;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * v2 shape: a BREAKING change from v1 (author becomes an object, year is renamed).
 * That's why it lives under /api/v2 and v1 keeps working (see "Versioning").
 *
 * @mixin Book
 */
class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => ['name' => $this->author],
            'year' => $this->published_year,
            'genre' => $this->genre,
        ];
    }
}
