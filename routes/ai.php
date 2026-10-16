<?php

use App\Mcp\Servers\ByteStreakServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP servers (laravel/mcp)
|--------------------------------------------------------------------------
| POST /mcp — lets an admin's AI assistant add questions. Authenticate with a Sanctum
| token from the profile page: `Authorization: Bearer <token>`.
*/

Mcp::web('/mcp', ByteStreakServer::class)
    ->middleware(['auth:sanctum', 'admin', 'throttle:60,1'])
    ->name('mcp');
