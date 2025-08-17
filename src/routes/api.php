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
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentExerciseController;
use App\Http\Controllers\TeacherClassController;
use App\Http\Controllers\SessionExerciseController;
use App\Http\Controllers\SessionExerciseStudentController;

use App\Models\ClassModel;



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
Route::middleware('auth:sanctum')->get('/users/me', fn(\Illuminate\Http\Request $r) => $r->user());
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
Route::post('classes/{id}/students', [ClassController::class, 'addStudent']);
Route::put('classes/{classId}/students/{studentId}', [ClassController::class, 'updateStudent']);
Route::patch('classes/{classId}/students/{studentId}', [ClassController::class, 'patchStudent']);
Route::delete('classes/{classId}/students/{studentId}', [ClassController::class, 'removeStudent']);



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

//Route::put('classes-exceptions/{class_exception}', [ClassExceptionController::class, 'update']);
Route::apiResource('classes-exceptions', ClassExceptionController::class)->only(['index', 'show', 'store', 'update', 'destroy']);


// Related resources
Route::post('lessons', [LessonController::class, 'store']);
Route::put('lessons/{id}', [LessonController::class, 'update']);
Route::apiResource('lessons', LessonController::class)->only(['index', 'show']);
Route::get('lessons-teachers', [LessonController::class, 'withTeachers']);


Route::apiResource('students', StudentController::class);
Route::apiResource('students.attendance', StudentAttendanceController::class)->only(['index', 'store', 'update', 'destroy', 'show']);

Route::apiResource('students.payments', StudentPaymentController::class)
    ->parameters([
        'students' => 'user',        // bind {students} to App\Models\User
        'payments' => 'payment',     // bind {payment} to App\Models\StudentPayment
    ])->only(['index', 'show', 'store', 'update', 'destroy']);


Route::apiResource('teachers', TeacherController::class);
Route::apiResource('teachers.classes', TeacherClassController::class)->parameters(['classes' => 'class']); // {class} will be bound to ClassModel
Route::apiResource('teachers.salaries', TeacherSalaryController::class);


// Public (read-only)
Route::apiResource('membership-plans', MembershipPlanController::class)
    ->only(['index','show']);
// Admin (write)
Route::middleware(['auth:sanctum','can:manage-membership-plans'])->group(function () {
    Route::apiResource('membership-plans', MembershipPlanController::class)->only(['store','update','destroy']);
});

// public read
Route::apiResource('offers', OfferController::class)->only(['index','show']);
// admin write
Route::middleware(['auth:sanctum','can:manage-offers'])->group(function () {
    Route::apiResource('offers', OfferController::class)->only(['store','update','destroy']);
});

// public read
Route::apiResource('payment-methods', PaymentMethodController::class)->only(['index','show']);
// admin write
Route::middleware(['auth:sanctum','can:manage-payment-methods'])->group(function () {
    Route::apiResource('payment-methods', PaymentMethodController::class)->only(['store','update','destroy']);
});

// public read
Route::apiResource('exercises', ExerciseController::class)->only(['index','show']);

// admin write
Route::middleware(['auth:sanctum','can:manage-exercises'])->group(function () {
    Route::apiResource('exercises', ExerciseController::class)->only(['store','update','destroy']);
});


// Everyone can read sessions
Route::apiResource('classes.sessions', ClassSessionController::class)
    ->only(['index', 'show']);

// Only admins (via Sanctum + Gate) can write sessions
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('classes.sessions', ClassSessionController::class)
        ->only(['store', 'update', 'destroy']);
});

// Everyone can read session exercises
Route::apiResource('classes.sessions.exercises', SessionExerciseController::class)
    ->only(['index', 'show']);

// Only admins can write session exercises
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('classes.sessions.exercises', SessionExerciseController::class)
        ->only(['store', 'update', 'destroy']);
});


Route::apiResource('session-exercise-students', SessionExerciseStudentController::class)
    ->only(['index','show']);

// Only admins can write session_exercise_students
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('session-exercise-students', SessionExerciseStudentController::class)
        ->only(['store','update','destroy']);
});


// Everyone can read attendances
Route::apiResource('classes.sessions.attendances', AttendanceController::class)
    ->only(['index','show']);

// Only admins can write
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('classes.sessions.attendances', AttendanceController::class)
        ->only(['store','update','destroy']);
});



Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UserController::class);
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
Route::get('/debug', fn() => response()->json(['works' => true]));