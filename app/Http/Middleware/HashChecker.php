<?php

namespace App\Http\Middleware;

use App\Models\Token;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class HashChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(!$request->hasHeader('Authorization'))
        {
            return response()->json(['status' => 'not authorized', 'time' => Carbon::now()], 401);
        }

        $token = Token::where('token', $request->header('Authorization'))->where('is_used', false)->first();

        if(!$token)
        {
            return response()->json(['status' => 'not authorized', 'time' => Carbon::now()], 401);
        } else {
            $token->is_used = true;
            $token->save();
        }

        return $next($request);
    }
}
