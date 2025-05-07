<?php
use Illuminate\Support\Facades\Route;

Route::get('/classes', function () {
    return response()->json([
        [
            'title' => 'Boxing',
            'description' => 'Train like a champion with our boxing classes.',
            'image' => 'https://placehold.co/400x300'
        ],
        [
            'title' => 'Muay Thai',
            'description' => 'Master the art of striking with Muay Thai.',
            'image' => 'https://placehold.co/400x300'
        ],
        [
            'title' => 'Brazilian Jiu-Jitsu',
            'description' => 'Get fit with our expert-led BJJ training.',
            'image' => 'https://placehold.co/400x300'
        ]
    ]);
});
