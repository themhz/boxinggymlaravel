<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\StudentExerciseController;
use App\Http\Controllers\StudentClassController;
use App\Http\Controllers\ClassStudentController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\ClassExceptionController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\TeacherClassController;
use App\Http\Controllers\TeacherSalaryController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\SessionExerciseController;
use App\Http\Controllers\SessionExerciseStudentController;
use App\Http\Controllers\ClassSessionAttendanceController;

/*
|--------------------------------------------------------------------------
| Authentication & Account Management
|--------------------------------------------------------------------------
*/

// Login / token endpoints
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/login', fn () => response()->json(['status' => 'no login bro']));

// Password reset & forgot password
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/reset-password', function (Request $r) {
    return response()->json([
        'message' => 'Here is your reset token.',
        'token'   => $r->query('token'),
        'email'   => $r->query('email'),
    ]);
})->name('password.reset');

// Email verification notice / fulfillment
Route::get(
    '/email/verify/{id}/{hash}',
    function (EmailVerificationRequest $request) {
        $request->fulfill();
        return response()->json(['message' => 'Email verified successfully!']);
    }
)->middleware(['auth:sanctum', 'signed'])
 ->name('verification.verify');

// Current user info (authenticated)
Route::middleware('auth:sanctum')->get('/users/me', fn (Request $r) => $r->user());

// Authenticated account actions (logout, profile, tokens)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $r) {
        // revoke current token
        $r->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    });

    Route::get('/profile', fn () => response()->json(['status' => 'access is granted']));

    Route::get('/tokens', function (Request $r) {
        $tokens = $r->user()->tokens->map(fn ($token) => [
            'id'         => $token->id,
            'name'       => $token->name,
            'created_at' => $token->created_at,
            'last_used'  => $token->last_used_at,
            'is_current' => $token->id === optional($r->user()->currentAccessToken())->id,
        ]);
        return response()->json(['tokens' => $tokens]);
    });
});

/*
|--------------------------------------------------------------------------
| Public (read‑only) API resources
|--------------------------------------------------------------------------
| These routes allow anyone to list and view the resources.  They do not
| permit creation, updating or deletion.
*/

Route::apiResource('students', StudentController::class)->only(['index', 'show']);
Route::apiResource('classes', ClassController::class)->only(['index', 'show']);
Route::apiResource('lessons', controller: LessonController::class)->only(['index', 'show']);
Route::apiResource('teachers', controller: TeacherController::class)->only(['index', 'show']);
Route::apiResource('exercises', ExerciseController::class)->only(['index', 'show']);
Route::apiResource('offers', controller: OfferController::class)->only(['index', 'show']);


Route::apiResource('membership-plans', MembershipPlanController::class)->only(['index', 'show']);
Route::apiResource('payment-methods', PaymentMethodController::class)->only(['index', 'show']);
Route::apiResource('sessions.exercises', SessionExerciseController::class)
    ->parameters(['sessions' => 'session', 'exercises' => 'session_exercise'])
    ->only(['index', 'show']);


/*
|--------------------------------------------------------------------------
| Custom single‑use routes
|--------------------------------------------------------------------------
| These don't fit neatly into an apiResource.
*/

// Class schedule (all users can read/write via dedicated endpoints)
Route::get('classes-schedule', [ClassController::class, 'schedule']);
Route::post('classes-schedule', [ClassController::class, 'store']);
Route::match(['put', 'patch'], 'classes-schedule/{id}', [ClassController::class, 'update']);
Route::delete('classes-schedule/{id}', [ClassController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Authenticated write (admin) routes
|--------------------------------------------------------------------------
| All of the routes below require Sanctum authentication.  Each resource
| also has its own policy/gate via `can:manage-…` middleware.  Adjust
| these policy names to match your actual Gate definitions.
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('classes.sessions.attendances', ClassSessionAttendanceController::class)
    ->only(['index', 'show']);
    
    Route::apiResource('students.payments', StudentPaymentController::class)->only(['index', 'show']);
    Route::apiResource('students.exercises', StudentExerciseController::class)
        ->parameters(['exercises' => 'student_exercise'])
        ->only(['index', 'show']);

    Route::apiResource('students.classes', StudentClassController::class)
        ->parameters(['classes' => 'class'])
        ->only(['index', 'show']);
    Route::apiResource('classes.students', ClassStudentController::class)
        ->parameters(['classes' => 'class'])
        ->only(['index', 'show']);
    Route::apiResource('classes.sessions', ClassSessionController::class)
        ->parameters(['classes' => 'class', 'sessions' => 'session'])
        ->only(['index', 'show']);
    Route::apiResource('classes.exceptions', ClassExceptionController::class)
        ->parameters(['classes' => 'class', 'exceptions' => 'exception'])
        ->only(['index', 'show']);
    Route::apiResource('teachers.classes', TeacherClassController::class)
        ->parameters(['classes' => 'class'])
        ->only(['index', 'show']);
    Route::apiResource('teachers.salaries', TeacherSalaryController::class)->only(['index', 'show']);


    Route::apiResource('users', UserController::class)->only(['index', 'show']);

    // Students
    Route::apiResource('students', StudentController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-students');

    // Classes
    Route::apiResource('classes', ClassController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-classes');

    // Lessons
    Route::apiResource('lessons', LessonController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-lessons');

    // Teachers
    Route::apiResource('teachers', TeacherController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-teachers');

    // Exercises
    Route::apiResource('exercises', ExerciseController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-exercises');

    // Offers
    Route::apiResource('offers', OfferController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-offers');

    // Users (full CRUD for admins)
    Route::apiResource('users', UserController::class);

    // Student payments
    Route::apiResource('students.payments', StudentPaymentController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-student-payments');

    Route::apiResource('students.payments', StudentPaymentController::class)
        ->only(['index', 'show'])
        // The policy method should verify user can view this student's payments
        ->middleware('can:view-student-payments,student');

    // Students → Exercises        
    Route::apiResource('students.exercises', StudentExerciseController::class)
        ->parameters(['exercises' => 'student_exercise'])
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-student-exercises');

    Route::apiResource('students.exercises', StudentExerciseController::class)
        ->parameters(['exercises' => 'student_exercise'])
        ->only(['index', 'show'])
        ->middleware('can:view-student-exercises,student');

    // Student classes
    Route::apiResource('students.classes', StudentClassController::class)
        ->parameters(['classes' => 'class'])
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-student-classes');

    // Class → Students
    Route::apiResource('classes.students', ClassStudentController::class)
        ->parameters(['classes' => 'class'])
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-class-students');

    // Class sessions
    Route::apiResource('classes.sessions', ClassSessionController::class)
        ->parameters(['classes' => 'class', 'sessions' => 'session'])
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-class-sessions');

    // Class exceptions
    Route::apiResource('classes.exceptions', ClassExceptionController::class)
        ->parameters(['classes' => 'class', 'exceptions' => 'exception'])
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-class-exceptions');

    // Student attendance
    Route::apiResource('students.attendance', StudentAttendanceController::class)
        ->only(['index', 'store', 'update', 'destroy', 'show'])
        ->middleware('can:manage-student-attendance');

    // Teachers → Classes (pivot)
    Route::apiResource('teachers.classes', TeacherClassController::class)
        ->parameters(['classes' => 'class'])
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-teacher-classes');

    // Teachers → Salaries
    Route::apiResource('teachers.salaries', TeacherSalaryController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-teacher-salaries');

    // Membership plans
    Route::apiResource('membership-plans', MembershipPlanController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-membership-plans');

    // Payment methods
    Route::apiResource('payment-methods', PaymentMethodController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-payment-methods');

    // Session → Exercises
    Route::apiResource('sessions.exercises', SessionExerciseController::class)
        ->parameters(['sessions' => 'session', 'exercises' => 'session_exercise'])
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-session-exercises');

    // Sessions → Exercises → Students
    // First, constrain wildcards so "students" won't be captured as an ID
    Route::pattern('session', '[0-9]+');
    Route::pattern('session_exercise', '[0-9]+');
    Route::pattern('student_exercise', '[0-9]+');
    Route::pattern('student', '[0-9]+');

    // Literal route: GET /api/sessions/{session}/exercises/students
    Route::get(
        'sessions/{session}/exercises/students',
        [SessionExerciseStudentController::class, 'indexForSession']
    )->name('sessions.exercises.students.all');

    // Nested resource routes
    Route::apiResource('sessions.exercises.students', SessionExerciseStudentController::class)
        ->parameters([
            'sessions'  => 'session',
            'exercises' => 'student_exercise',
            'students'  => 'student',
        ])
        ->only(['index', 'show']);

    Route::apiResource('sessions.exercises.students', SessionExerciseStudentController::class)
        ->parameters([
            'sessions'  => 'session',
            'exercises' => 'student_exercise',
            'students'  => 'student',
        ])
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-session-exercise-students');

    // Class session attendances
    Route::apiResource('classes.sessions.attendances', ClassSessionAttendanceController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:manage-class-session-attendances');
});
