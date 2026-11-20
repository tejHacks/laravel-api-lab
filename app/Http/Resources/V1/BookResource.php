<?php

namespace App\Http\Resources\V1;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * v1 shape of a book. Decides exactly what JSON goes out (see "API Resources").
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
            'author' => $this->author,
            'genre' => $this->genre,
            'published_year' => $this->published_year,
            'pages' => $this->pages,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'is_classic' => $this->published_year !== null && $this->published_year < 1980,
            'added_by' => $this->whenLoaded('owner', fn () => $this->owner?->name),
            'can_edit' => $request->user()?->id !== null && $request->user()->id === $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'links' => ['self' => route('v1.books.show', $this->resource)],
        ];
    }
}
