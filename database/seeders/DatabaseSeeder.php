<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'Demo Reader',
            'email' => 'reader@example.com',
            'password' => 'password',
        ]);

        $classics = [
            ['Things Fall Apart', 'Chinua Achebe', 'fiction', 1958],
            ['Half of a Yellow Sun', 'Chimamanda Ngozi Adichie', 'fiction', 2006],
            ['Purple Hibiscus', 'Chimamanda Ngozi Adichie', 'fiction', 2003],
            ['The Famished Road', 'Ben Okri', 'fiction', 1991],
            ['Americanah', 'Chimamanda Ngozi Adichie', 'fiction', 2013],
            ['The Pragmatic Programmer', 'Andrew Hunt & David Thomas', 'tech', 1999],
            ['Clean Code', 'Robert C. Martin', 'tech', 2008],
            ['Refactoring', 'Martin Fowler', 'tech', 1999],
            ['Long Walk to Freedom', 'Nelson Mandela', 'history', 1994],
            ['The Joys of Motherhood', 'Buchi Emecheta', 'fiction', 1979],
        ];

        foreach ($classics as [$title, $author, $genre, $year]) {
            $demo->books()->create([
                'title' => $title,
                'author' => $author,
                'genre' => $genre,
                'published_year' => $year,
            ]);
        }

        Book::factory()->count(14)->create();
    }
}
