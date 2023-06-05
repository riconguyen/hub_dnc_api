<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use Exception;
class authJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::toUser($request->bearerToken());
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['error'=>'Token is Invalid']);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['error'=>'Token is Expired']);
            }else{
                return response()->json(['error'=>'Something is wrong']);
            }
        }
        $request->user=$user;

        $apiSource= $request->header("Source");
        $request->api_source=$apiSource?false:true;

        if($request->method() !="GET")
        {
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->except("token","password")) . "|BEGIN_REQUEST");
        }



      return $next($request);
    }

}
