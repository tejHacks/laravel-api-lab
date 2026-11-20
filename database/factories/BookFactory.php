<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement([
                'The Silent River', 'Code of the Harmattan', 'Midnight in Lagos', 'A Garden of Numbers',
                'Echoes of Kilimanjaro', 'The Last Algorithm', 'Songs for the Savannah', 'Paper Lanterns',
                'Learning to Fly', 'The Clockmaker\'s Daughter', 'Salt and Sunlight', 'Streets of Accra',
                'The Patient Compiler', 'Under the Baobab', 'Northern Lights', 'The Quiet Engineer',
            ]),
            'author' => fake()->name(),
            'genre' => fake()->randomElement(Book::GENRES),
            'published_year' => fake()->numberBetween(1950, (int) date('Y')),
            'pages' => fake()->numberBetween(90, 700),
            'isbn' => fake()->unique()->isbn13(),
            'description' => fake()->randomElement([
                'A moving story about family, ambition and home.',
                'A practical guide packed with real-world examples.',
                'Short, sharp and impossible to put down.',
                null,
            ]),
        ];
    }
}
