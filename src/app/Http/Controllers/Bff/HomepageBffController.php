<?php
namespace App\Http\Controllers\Bff;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;

class HomepageBffController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $classes = Lesson::take(3)->get()->map(function ($lesson) {
            return [
                'title' => $lesson->title,
                'description' => $lesson->description,
                'image' => $lesson->image,
            ];
        });

        return response()->json([
            'hero' => [
                'headline' => 'Welcome to IronFist Gym',
                'subtext' => 'Train your body and mind with top-tier martial arts coaching.',
                'cta_text' => 'Join a Class',
                'cta_link' => 'classes.html',
                'alt_text' => 'Book Free Trial',
                'alt_link' => 'appointment.html',
            ],
            'carousel' => [
                [
                    'video' => '/templates/boxinggym/boxinggym1/assets/video/video1.mp4',
                    'title' => 'Boxing Classes',
                    'description' => 'Train like a champion with our boxing classes.',
                ],
                [
                    'video' => '/templates/boxinggym/boxinggym1/assets/video/video2.mp4',
                    'title' => 'Muay Thai Training',
                    'description' => 'Master the art of striking with our Muay Thai training.',
                ],
                [
                    'video' => '/templates/boxinggym/boxinggym1/assets/video/video3.mp4',
                    'title' => 'Brazilian Jiu-Jitsu',
                    'description' => 'Learn self-defense and improve your fitness with BJJ.',
                ],
            ],
            'about' => [
                'title' => 'About Our Gym',
                'description' => 'At IronFist Gym, we help you achieve your fitness and martial arts goals. Our instructors and community make it the perfect place to grow.',
                'button_text' => 'Learn More',
                'button_link' => 'about.html',
            ],
            'classes' => $classes,
            'testimonials' => [
                [
                    'quote' => '"IronFist Gym changed my life! Amazing trainers and community."',
                    'author' => 'John Doe',
                ],
                [
                    'quote' => '"I love the variety of classes and friendly atmosphere!"',
                    'author' => 'Jane Smith',
                ],
            ]
        ]);
    }
}
