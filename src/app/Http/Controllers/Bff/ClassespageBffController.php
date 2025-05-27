<?php
namespace App\Http\Controllers\Bff;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\JsonResponse;

class ClassespageBffController extends Controller
{
    public function index(): JsonResponse
    {
        $classes = ClassModel::with('lesson')->get();

        // 1. Offerings section (unique lessons)
        $offerings = $classes
            ->map(fn($c) => [
                'id'          => $c->lesson->id,
                'title'       => $c->lesson->title,
                'description' => $c->lesson->description,
                'image'       => $c->lesson->image,
            ])
            ->unique('id')
            ->values();

        // 2. Schedule section (grouped by day)
        $schedule = $classes
            ->groupBy('day')
            ->mapWithKeys(fn($group, $day) => [
                $day => $group->map(fn($c) => [
                    'class'      => $c->lesson->title,
                    'start_time' => $c->start_time,
                    'end_time'   => $c->end_time,
                    'capacity'   => $c->capacity,
                ])->values()
            ]);

        return response()->json([
            'offerings' => $offerings,
            'schedule'  => $schedule,
        ]);
    }
}
