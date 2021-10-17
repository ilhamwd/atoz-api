<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;
use App\Models\UserSessions;

class Authorization
{
    public function handle($request, Closure $next)
    {
        // The authorization header contains
        // Basic base64(user_uuid:token)
        $authorization_header = str_replace("Basic ", "", $request->header("authorization"));
        $authorization_header = base64_decode($authorization_header);
        [$user_uuid, $token] = explode(":", $authorization_header);

        // Check if token belongs to the user_uuid
        $session_check = UserSessions::where([
            ['user_uuid', '=', $user_uuid],
            ['token', '=', $token]
        ])->first();

        if (!$session_check) return response([
            'status' => 401,
            'message' => 'Unauthorized'
        ], 401);

        define("USER_UUID", $user_uuid);
        define("TIMEZONE", 7 * 60 * 60);

        return $next($request);
    }
}
