<?php

use App\Http\Controllers\Bff\HomepageBffController;
use App\Http\Controllers\Bff\ClassespageBffController;

Route::get('/homepage', HomepageBffController::class);

Route::get('/about', function () {
    return response()->json([
        'hero' => [
            'title' => 'About IronFist Gym',
            'subtitle' => 'Where Passion Meets Discipline'
        ],
        'story' => [
            'title' => 'Our Story',
            'intro' => 'Founded in 2015, IronFist Gym was born out of a passion for martial arts and a desire to create a community where everyone feels welcome. Whether you\'re a beginner or a seasoned fighter, we’re here to help you achieve your goals.',
            'mission' => 'Our gym is more than just a place to train—it’s a family. We believe in the power of martial arts to transform lives, build confidence, and foster discipline.',
            'image' => 'https://placehold.co/600x400'
        ],
        'team' => [
            [
                'name' => 'John Doe',
                'role' => 'Head Boxing Coach',
                'description' => 'With over 10 years of experience, John has trained champions and beginners alike.',
                'image' => 'https://placehold.co/300x300'
            ],
            [
                'name' => 'Jane Smith',
                'role' => 'Muay Thai Specialist',
                'description' => 'Jane brings a wealth of knowledge and a passion for teaching Muay Thai.',
                'image' => 'https://placehold.co/300x300'
            ],
            [
                'name' => 'Mike Johnson',
                'role' => 'BJJ Black Belt',
                'description' => 'Mike is dedicated to helping students master the art of Brazilian Jiu-Jitsu.',
                'image' => 'https://placehold.co/300x300'
            ]
        ],
        'why_choose_us' => [
            [
                'title' => 'Expert Instructors',
                'description' => 'Our coaches are highly trained and passionate about helping you succeed.'
            ],
            [
                'title' => 'State-of-the-Art Facilities',
                'description' => 'Train in a clean, modern, and fully equipped gym.'
            ],
            [
                'title' => 'Supportive Community',
                'description' => 'Join a welcoming community that motivates and inspires you.'
            ]
        ]
    ]);
});


Route::get('/classpage', [ClassespageBffController::class, 'index']);