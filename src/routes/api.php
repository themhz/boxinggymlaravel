<?php
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ClassTypeController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentAvailabilityController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\LessonController;
use App\Models\Lesson;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\OfferController;
use App\Models\MembershipPlan;
use App\Models\Offer;

Route::get('/homepage', function () {
    // Grab the first 3 lessons (or whatever logic you prefer)
    $classes = Lesson::take(3)->get()->map(function ($lesson) {
        return [
            'title'       => $lesson->title,
            'description' => $lesson->description,
            // Adjust this if you store images elsewhere; asset() will generate a full URL
            'image'       => $lesson->image,
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
});

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


Route::get('/pricing', function () {

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



Route::get('/appointments/availability', function () {
    return response()->json([
        "09:00" => [ "Mon" => true,  "Tue" => true,  "Wed" => false, "Thu" => true,  "Fri" => true,  "Sat" => false, "Sun" => false ],
        "10:00" => [ "Mon" => true,  "Tue" => false, "Wed" => true,  "Thu" => true,  "Fri" => false, "Sat" => true,  "Sun" => false ],
        "11:00" => [ "Mon" => false, "Tue" => true,  "Wed" => true,  "Thu" => false, "Fri" => true,  "Sat" => true,  "Sun" => false ],
        "12:00" => [ "Mon" => true,  "Tue" => true,  "Wed" => true,  "Thu" => true,  "Fri" => true,  "Sat" => false, "Sun" => false ]
    ]);
});


Route::post('/appointments/book', function (Request $request) {
    // For now, just log the appointment data to Laravel logs
    Log::info('Appointment booked:', $request->all());

    return response()->json([
        'status' => 'success',
        'message' => 'Appointment received (mock response)',
        'data' => $request->all()
    ]);
});


Route::get('/contact-info', function () {
    return response()->json([
        'hero' => [
            'title' => 'Contact Us',
            'subtitle' => 'We\'d love to hear from you! Reach out for inquiries, feedback, or to schedule a visit.',
        ],
        'location' => [
            'address' => '123 Gym Street, City, State, ZIP Code',
            'phone' => '(123) 456-7890',
            'email' => 'info@yourgym.com',
            'map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3153.8354345093747!2d144.95373531531615!3d-37.816279742021665!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6ad642af0f11fd81%3A0xf577d44e7c30b4df!2sYour%20Gym%20Name!5e0!3m2!1sen!2sus!4v1633023226785!5m2!1sen!2sus', // your real URL here
        ]
    ]);
});

Route::post('/contact-message', function (Request $request) {
    Log::info('Contact Form Submission', $request->all());

    return response()->json(['message' => 'Message received successfully!']);
});


Route::post('/signup-preview', function (Request $request) {
    Log::info('Signup form data:', $request->all());
    return response()->json(['status' => 'ok']);
});


// Remove any existing login routes first
Route::post('/login', [AuthController::class, 'login'])->name('api.login'); // Name it differently



Route::get('/login', function (Request $request) {
    
    return response()->json(['status' => 'no login bro']);
});


Route::post('/login', [AuthController::class, 'login'])->name('login'); // Changed to 'login'

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logged out successfully']);
});



// Protected routes
Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {    
    return response()->json(['status' => 'access is granted']);
});

Route::post('/register', [AuthController::class, 'register']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/reset-password', function (Request $request) {
    return response()->json([
        'message' => 'Here is your reset token.',
        'token' => $request->query('token'),
        'email' => $request->query('email'),
    ]);
})->name('password.reset');

Route::any('/test-route', function () {
    return response()->json(['message' => 'Fallback route working']);
});


Route::post('/debug-reset', function () {
    return response()->json(['message' => 'You hit the API route!']);
});

Route::get('/classes/available', [ClassController::class, 'available']);
//Route::post('/availability', [AppointmentAvailabilityController::class, 'store']);


Route::middleware('auth:sanctum')->get('/tokens', function (Request $request) {
    $user = $request->user();
    $currentTokenId = $user->currentAccessToken()?->id;

    return response()->json([
        'tokens' => $user->tokens->map(function ($token) use ($currentTokenId) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
                'is_current' => $token->id === $currentTokenId,
            ];
        })
    ]);
});

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return response()->json(['message' => 'Email verified successfully!']);
})->middleware(['auth:sanctum', 'signed'])->name('verification.verify');



// Publicly readable routes
Route::apiResource('teams', TeamController::class)->only(['index', 'show']);
Route::apiResource('teachers', TeacherController::class)->only(['index', 'show']);
Route::apiResource('students', StudentController::class)->only(['index', 'show']);
Route::apiResource('class-types', ClassTypeController::class)->only(['index', 'show']);
Route::apiResource('classes', ClassController::class)->only(['index', 'show']);
Route::apiResource('appointments', AppointmentController::class)->only(['index', 'show']);
Route::apiResource('posts', PostController::class)->only(['index', 'show']);
Route::apiResource('lessons', LessonController::class)->only(['index', 'show']);
//Route::apiResource('availability', AppointmentAvailabilityController::class)->only(['index', 'show']);
Route::get('/availability', [AvailabilityController::class, 'index']);

Route::apiResource('membership-plans', MembershipPlanController::class)->only(['index', 'show']);

Route::apiResource('offers', OfferController::class)->only(['index', 'show']);



// Protected write routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('teams', TeamController::class)->except(['index', 'show']);
    Route::apiResource('teachers', TeacherController::class)->except(['index', 'show']);
    Route::apiResource('students', StudentController::class)->except(['index', 'show']); //Working on
    Route::apiResource('class-types', ClassTypeController::class)->except(['index', 'show']);
    Route::apiResource('classes', ClassController::class)->except(['index', 'show']);
    Route::apiResource('appointments', AppointmentController::class)->except(['index', 'show']);
    Route::apiResource('availability', AppointmentAvailabilityController::class)->except(['index', 'show']);    
    Route::apiResource('posts', PostController::class)->except(['index', 'show']);    //OK
    Route::apiResource('lessons', LessonController::class)->except(['index', 'show']);    //OK
    Route::apiResource('membership-plans', MembershipPlanController::class)->except(['index', 'show']);
    Route::apiResource('offers', OfferController::class)->except(['index', 'show']);

});

if (app()->environment(['local', 'testing'])) {
    Route::get('/routes', function () {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'method' => implode('|', $route->methods()),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $route->gatherMiddleware(),
            ];
        });

        return response()->json($routes->values());
    });
}
