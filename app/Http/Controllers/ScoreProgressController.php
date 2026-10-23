<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Services\ClassProgressService;
use Illuminate\Http\JsonResponse;

class ScoreProgressController extends Controller
{
    public function show(SchoolClass $class, ClassProgressService $service): JsonResponse
    {
        $progress = $service->getProgress($class);

        return response()->json($progress);
    }
}
