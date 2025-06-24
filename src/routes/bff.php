<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Bff\HomepageBffController;
use App\Http\Controllers\Bff\ClassespageBffController;
use App\Http\Controllers\MembershipPlanController;
use App\Models\MembershipPlan;
use App\Models\Offer;

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

Route::get('/pricingpage', function () {

    $plans = MembershipPlan::all()->map(function($plan) {
        // Turn price + duration into a label, e.g. "$50/month" or "$499.99/year"
        $period = $plan->duration_days === 365 ? 'year' : 'month';
        $priceLabel = '$' . number_format($plan->price, 2) . '/' . $period;

        // Split the description into features by commas
        $features = array_map('trim', explode(',', $plan->description));

        return [
            'name'     => $plan->name,
            'price'    => $priceLabel,
            'features' => $features,
        ];
    });

    // 2) Pull offers (if you want these dynamic too)
    $offers = Offer::all()->map(fn($offer) => [
        'title'       => $offer->title,
        'description' => $offer->description,
    ]);

    return response()->json([
        'hero' => [
            'title' => 'Membership Plans',
            'subtitle' => 'Choose the plan that fits your goals and budget.'
        ],
        'plans' => $plans,
        'offers' => $offers,        
        'cta' => [
            'title' => 'Ready to Join?',
            'subtitle' => 'Sign up for a free trial class today and experience the [Your Gym Name] difference.'
        ]
    ]);
});


Route::get('/appointments', function () {
    return response()->json([
        "09:00" => [ "Mon" => true,  "Tue" => true,  "Wed" => false, "Thu" => true,  "Fri" => true,  "Sat" => false, "Sun" => false ],
        "10:00" => [ "Mon" => true,  "Tue" => false, "Wed" => true,  "Thu" => true,  "Fri" => false, "Sat" => true,  "Sun" => false ],
        "11:00" => [ "Mon" => false, "Tue" => true,  "Wed" => true,  "Thu" => false, "Fri" => true,  "Sat" => true,  "Sun" => false ],
        "12:00" => [ "Mon" => true,  "Tue" => true,  "Wed" => true,  "Thu" => true,  "Fri" => true,  "Sat" => false, "Sun" => false ],
        "12:55" => [ "Mon" => true,  "Tue" => false,  "Wed" => false,  "Thu" => false,  "Fri" => false,  "Sat" => false, "Sun" => false ],        
    ]);
});
