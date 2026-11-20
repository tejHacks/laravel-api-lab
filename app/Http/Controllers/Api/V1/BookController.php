<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 *   GET    /api/v1/books            index   (public: search, genre, sort, per_page)
 *   GET    /api/v1/books/{book}     show    (public)
 *   POST   /api/v1/books            store   (token with books:write)
 *   PATCH  /api/v1/books/{book}     update  (token with books:write + owner)
 *   DELETE /api/v1/books/{book}     destroy (token with books:write + owner)
 */
class BookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sortable = collect(Book::SORTABLE)->flatMap(fn ($c) => [$c, "-{$c}"])->all();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', Rule::in(Book::GENRES)],
            'sort' => ['nullable', Rule::in($sortable)],          // whitelist: never sort by arbitrary columns
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $sort = $filters['sort'] ?? '-created_at';

        $books = Book::query()
            ->with('owner')                                        // no N+1 in the resource
            ->search($filters['search'] ?? null)
            ->when($filters['genre'] ?? null, fn ($q, $g) => $q->where('genre', $g))
            ->orderBy(ltrim($sort, '-'), str_starts_with($sort, '-') ? 'desc' : 'asc')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return BookResource::collection($books);
    }

    public function store(StoreBookRequest $request)
    {
        $book = $request->user()->books()->create($request->validated());

        return (new BookResource($book->load('owner')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);           // 201
    }

    public function show(Book $book): BookResource
    {
        return new BookResource($book->load('owner'));
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        Gate::authorize('update', $book);                        // 403 unless owner

        $book->update($request->validated());

        return new BookResource($book->load('owner'));
    }

    public function destroy(Book $book): Response
    {
        Gate::authorize('delete', $book);

        $book->delete();

        return response()->noContent();                          // 204
    }
}
