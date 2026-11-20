# Laravel API Lab

A website that teaches **building APIs with Laravel 13, from basic to advanced** (18 lessons with quizzes), plus a real **Books API** and an in-browser **API Playground**.

## Run it
```bash
cd ~/Developer/laravel-api-lab
php artisan migrate:fresh --seed
php artisan serve        # course: http://127.0.0.1:8000   playground: /playground
```
Seeded API user: `reader@example.com` / `password`

## The API
| Method | URL | Auth |
|---|---|---|
| POST | /api/v1/auth/register, /auth/login | – |
| GET/POST | /api/v1/auth/me, /auth/logout | token |
| GET | /api/v1/books?search=&genre=&sort=-published_year&per_page= | – |
| GET | /api/v1/books/{id} | – |
| POST/PATCH/DELETE | /api/v1/books(/{id}) | token with `books:write` (+ owner) |
| GET | /api/v1/stats (cached), /api/v2/books (new shape) | – |

Tests: `php artisan test`
