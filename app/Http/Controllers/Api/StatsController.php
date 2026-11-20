<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Cached aggregate endpoint (see "Caching & Performance").
 */
class StatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $stats = Cache::remember('books.stats', now()->addMinute(), fn () => [
            'books' => Book::count(),
            'authors' => Book::distinct('author')->count('author'),
            'by_genre' => Book::selectRaw('genre, count(*) as total')
                ->groupBy('genre')
                ->orderByDesc('total')
                ->pluck('total', 'genre'),
            'cached_at' => now()->toIso8601String(),
        ]);

        return response()->json(['data' => $stats]);
    }
}
