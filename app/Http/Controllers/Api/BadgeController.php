<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function __invoke(Request $request, BadgeService $badges): JsonResponse
    {
        return response()->json(['data' => $badges->catalogue($request->user())]);
    }
}
