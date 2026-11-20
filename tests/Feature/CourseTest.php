<?php

namespace Tests\Feature;

use App\Lessons\LessonRepository;
use Tests\TestCase;

class CourseTest extends TestCase
{
    public function test_home_lists_every_module(): void
    {
        $response = $this->get('/')->assertOk();

        foreach (app(LessonRepository::class)->modules() as $module) {
            $response->assertSee($module['title']);
        }
    }

    public function test_every_lesson_renders_with_its_quiz(): void
    {
        $lessons = app(LessonRepository::class);

        foreach ($lessons->outline() as $entry) {
            $lesson = $lessons->find($entry['slug']);

            $response = $this->get(route('lessons.show', $entry['slug']))->assertOk()->assertSee($lesson->title);
            $this->assertStringNotContainsString(':::', $lesson->html, "Unrendered callout in {$entry['slug']}");
            $this->assertStringNotContainsString('```', $lesson->html, "Unrendered fence in {$entry['slug']}");

            if ($lesson->quiz) {
                $response->assertSee('Check your understanding');
            }
        }
    }

    public function test_callouts_and_code_are_rendered(): void
    {
        $html = app(LessonRepository::class)->find('what-is-an-api')->html;

        $this->assertStringContainsString('class="callout callout-analogy"', $html);
        $this->assertStringContainsString('<pre><code class="language-http">', $html);
    }

    public function test_unknown_lesson_is_404(): void
    {
        $this->get('/lessons/does-not-exist')->assertNotFound();
    }

    public function test_completing_a_lesson_tracks_progress_and_moves_on(): void
    {
        $this->post(route('lessons.complete', 'what-is-an-api'))
            ->assertRedirect(route('lessons.show', 'http-and-json'))
            ->assertSessionHas('progress.completed', ['what-is-an-api']);

        $this->get('/')->assertSee('1</strong> of', false);

        $this->post(route('lessons.complete', 'what-is-an-api'), ['undo' => 1])
            ->assertSessionHas('progress.completed', []);
    }

    public function test_search_finds_lessons_by_content(): void
    {
        $this->get('/search?q=sanctum')
            ->assertOk()
            ->assertSee('Token Authentication with Sanctum', false);

        $this->get('/search?q=zzzznotathing')->assertSee('No lessons match');
    }
}
