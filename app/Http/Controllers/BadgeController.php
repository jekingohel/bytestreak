<?php

namespace App\Http\Controllers;

use App\Services\BadgeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BadgeController extends Controller
{
    public function __invoke(Request $request, BadgeService $badges): Response
    {
        return Inertia::render('Badges', [
            'badges' => $badges->catalogue($request->user()),
        ]);
    }
}
