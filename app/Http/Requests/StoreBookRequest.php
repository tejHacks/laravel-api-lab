<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware already requires a token with books:write
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'author' => ['required', 'string', 'max:120'],
            'genre' => ['required', Rule::in(Book::GENRES)],
            'published_year' => ['nullable', 'integer', 'between:1000,'.date('Y')],
            'pages' => ['nullable', 'integer', 'between:1,20000'],
            'isbn' => ['nullable', 'string', 'max:20', Rule::unique('books', 'isbn')->ignore($this->route('book'))],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
