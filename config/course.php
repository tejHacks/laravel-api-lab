<?php

return [
    'path' => resource_path('lessons'),
    'accent' => env('COURSE_ACCENT', '#0E7490'),

    // Links in the top menu: [label, route name, route pattern that marks it "active"].
    'demos' => [
        ['API Playground', 'playground', 'playground'],
    ],
];
