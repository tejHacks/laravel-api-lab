<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

/**
 * Only the user who added a book may change or delete it.
 * Found automatically for App\Models\Book by naming convention.
 */
class BookPolicy
{
    public function update(User $user, Book $book): bool
    {
        return $book->user_id === $user->id;
    }

    public function delete(User $user, Book $book): bool
    {
        return $book->user_id === $user->id;
    }
}
