<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'author', 'genre', 'published_year', 'pages', 'isbn', 'description'])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    public const GENRES = ['fiction', 'non-fiction', 'poetry', 'tech', 'history', 'children'];

    /** Columns clients may sort by (see "Pagination, Filtering & Sorting"). */
    public const SORTABLE = ['title', 'author', 'published_year', 'created_at'];

    protected function casts(): array
    {
        return [
            'published_year' => 'integer',
            'pages' => 'integer',
        ];
    }

    /** The user who added the book (only they may change it). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $q, string $t) => $q->where(fn (Builder $q) => $q
            ->where('title', 'like', "%{$t}%")
            ->orWhere('author', 'like', "%{$t}%")));
    }
}
