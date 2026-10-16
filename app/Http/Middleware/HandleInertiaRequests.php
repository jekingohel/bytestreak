<?php

namespace App\Http\Middleware;

use App\Services\ProgressService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'is_local' => app()->isLocal(),
            ],
            'auth' => [
                'user' => fn () => $request->user() ? app(ProgressService::class)->player($request->user()) : null,
            ],
        ];
    }
}
