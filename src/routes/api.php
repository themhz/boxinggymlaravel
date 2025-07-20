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
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\PaymentMethodController;



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

//Route::get('/classes/available', [ClassController::class, 'available']);
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
Route::apiResource('classes', controller: ClassController::class)->only(['index', 'show']);
Route::get('classes-schedule', [ClassController::class, 'schedule']); // for schedule
Route::apiResource('lessons', LessonController::class)->only(['index', 'show']);
Route::get('lessons-teachers', [LessonController::class, 'withTeachers']);
Route::apiResource('students', StudentController::class)->only(['index', 'show']);
Route::get('students/{id}/lessons', [StudentController::class, 'studentLessons']);

Route::apiResource('teachers', TeacherController::class)->only(['index', 'show']);
Route::get('teachers/{id}/lessons', [TeacherController::class, 'lessons']);

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);

Route::apiResource('membership-plans', MembershipPlanController::class)->only(['index', 'show']);
Route::apiResource('offers', OfferController::class)->only(['index', 'show']);


//Route::apiResource('student-payments', StudentPaymentController::class)->only(['index', 'show']);
// All payments of a specific student
Route::get('/students/{user}/payments', [StudentPaymentController::class, 'byStudent']);

// A specific payment of a student
Route::get('/students/{user}/payments/{payment}', [StudentPaymentController::class, 'studentPaymentShow']);

Route::get('/payment-methods', [PaymentMethodController::class, 'index']);



// Protected write routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('teams', TeamController::class)->except(['index', 'show']);
    Route::apiResource('teachers', TeacherController::class)->except(['index', 'show']);
    Route::apiResource('students', StudentController::class)->except(['index', 'show']); //Working on
    //Route::apiResource('class-types', ClassTypeController::class)->except(['index', 'show']);
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
