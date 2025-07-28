<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherSalaryController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ClassTypeController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentAvailabilityController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\ClassExceptionController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\AttendanceController;

use App\Models\ClassModel;

// Public utility routes
Route::post('/appointments/book', fn(Request $request) => response()->json([
    'status' => 'success',
    'message' => 'Appointment received (mock response)',
    'data' => tap($request->all(), fn($data) => Log::info('Appointment booked:', $data))
]));

Route::post('/contact-message', fn(Request $request) => response()->json([
    'message' => 'Message received successfully!'
]));

Route::post('/signup-preview', fn(Request $request) => response()->json(['status' => 'ok']));

Route::get('/contact-info', fn() => response()->json([
    'hero' => [
        'title' => 'Contact Us',
        'subtitle' => 'We\'d love to hear from you!'
    ],
    'location' => [
        'address' => '123 Gym Street, City',
        'phone' => '(123) 456-7890',
        'email' => 'info@yourgym.com',
        'map' => 'https://www.google.com/maps/embed?...'
    ]
]));

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/login', fn() => response()->json(['status' => 'no login bro']));
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/reset-password', fn(Request $r) => response()->json([
    'message' => 'Here is your reset token.',
    'token' => $r->query('token'),
    'email' => $r->query('email'),
]))->name('password.reset');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', fn(Request $r) => tap($r->user()->currentAccessToken()->delete(), fn() => response()->json(['message' => 'Logged out successfully'])));
    Route::get('/profile', fn() => response()->json(['status' => 'access is granted']));
    Route::get('/tokens', fn(Request $r) => response()->json([
        'tokens' => $r->user()->tokens->map(fn($token) => [
            'id' => $token->id,
            'name' => $token->name,
            'created_at' => $token->created_at,
            'last_used_at' => $token->last_used_at,
            'is_current' => $token->id === optional($r->user()->currentAccessToken())->id
        ])
    ]));
});

Route::get('/email/verify/{id}/{hash}', fn(EmailVerificationRequest $request) => tap($request->fulfill(), fn() => response()->json(['message' => 'Email verified successfully!'])))
    ->middleware(['auth:sanctum', 'signed'])
    ->name('verification.verify');


//Class Schedule Routes
Route::get('classes-schedule', [ClassController::class, 'schedule']);
Route::post('classes-schedule', [ClassController::class, 'store']);
Route::put('classes-schedule/{id}', [ClassController::class, 'update']);
Route::patch('classes-schedule/{id}', [ClassController::class, 'update']);
Route::delete('classes-schedule/{id}', [ClassController::class, 'destroy']);

//Classes API Routes
Route::get('classes/{id}/students', [ClassController::class, 'students']);
Route::get('classes/{id}', fn($id) => ClassModel::with(['teacher', 'students', 'lesson'])->findOrFail($id));
Route::post('classes', [ClassController::class, 'store']);
Route::put('classes/{id}', [ClassController::class, 'update']);
Route::patch('classes/{id}', [ClassController::class, 'update']);
Route::delete('classes/{id}', [ClassController::class, 'destroy']);

// Class Sessions & Exceptions
Route::get('classes-sessions', [ClassSessionController::class, 'apiClassesWithSessions']);
Route::get('classes-sessions/{id}', [ClassSessionController::class, 'apiClassSessionsById']);
Route::post('classes-sessions', [ClassSessionController::class, 'apiStore']);
Route::put('classes-sessions/{id}', [ClassSessionController::class, 'apiUpdate']);
Route::patch('classes-sessions/{id}', [ClassSessionController::class, 'apiUpdate']);
Route::delete('classes-sessions/{id}', [ClassSessionController::class, 'apiDestroy']);

Route::apiResource('classes-exceptions', ClassExceptionController::class)->only(['index', 'show', 'store', 'destroy']);

// Related resources
Route::post('lessons', [LessonController::class, 'store']);
Route::put('lessons/{id}', [LessonController::class, 'update']);
Route::apiResource('lessons', LessonController::class)->only(['index', 'show']);



Route::get('lessons-teachers', [LessonController::class, 'withTeachers']);
Route::apiResource('students', StudentController::class)->only(['index', 'show']);
Route::get('students/{id}/lessons', [StudentController::class, 'studentLessons']);
Route::get('students/{user}/payments', [StudentPaymentController::class, 'byStudent']);
Route::get('students/{user}/payments/{payment}', [StudentPaymentController::class, 'studentPaymentShow']);
Route::get('students/{id}/attendance', [ClassSessionController::class, 'apiStudentAttendance']);
Route::get('students/{id}/exercises', [ClassSessionController::class, 'apiStudentExercises']);

Route::apiResource('teachers', TeacherController::class)->only(['index', 'show']);
Route::get('teachers/{id}/lessons', [TeacherController::class, 'lessons']);
Route::get('teachers-salaries', [TeacherSalaryController::class, 'index']);
Route::get('teachers-salaries/{id}', [TeacherSalaryController::class, 'byUser']);
Route::post('teachers-salaries', [TeacherSalaryController::class, 'store']);

Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);

Route::apiResource('membership-plans', MembershipPlanController::class)->only(['index', 'show']);
Route::apiResource('offers', OfferController::class)->only(['index', 'show']);
Route::get('/payment-methods', [PaymentMethodController::class, 'index']);

Route::get('/sessions', [ClassSessionController::class, 'apiIndex']);
Route::get('/sessions/{id}', [ClassSessionController::class, 'apiShow']);
Route::get('/sessions/{id}/exercises', [ClassSessionController::class, 'apiSessionExercises']);

Route::get('/exercises', [ExerciseController::class, 'apiIndex']);
Route::get('/exercises/{id}', [ExerciseController::class, 'apiShow']);

Route::get('/attendances', [AttendanceController::class, 'apiIndex']);
Route::get('/attendances/{id}', [AttendanceController::class, 'apiShow']);

// Protected Write Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResources([
        'teams' => TeamController::class,
        'teachers' => TeacherController::class,
        'students' => StudentController::class,
        'classes' => ClassController::class,
        'appointments' => AppointmentController::class,
        'availability' => AppointmentAvailabilityController::class,
        'posts' => PostController::class,
        'lessons' => LessonController::class,
        'membership-plans' => MembershipPlanController::class,
        'offers' => OfferController::class,
    ]);
});

if (app()->environment(['local', 'testing'])) {
    Route::get('/routes', fn() => response()->json(collect(Route::getRoutes())->map(fn($route) => [
        'method' => implode('|', $route->methods()),
        'uri' => $route->uri(),
        'name' => $route->getName(),
        'action' => $route->getActionName(),
        'middleware' => $route->gatherMiddleware(),
    ])));
}

Route::any('/test-route', fn() => response()->json(['message' => 'Fallback route working']));
Route::post('/debug-reset', fn() => response()->json(['message' => 'You hit the API route!']));
