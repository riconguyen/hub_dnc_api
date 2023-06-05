<?php

namespace App\Http\Middleware;

use App\AppLog;
use Closure;
use Illuminate\Support\Facades\Log;

class CORS
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

        header("Access-Control-Allow-Origin: *");
    
        // ALLOW OPTIONS METHOD
        $headers = [
            'Access-Control-Allow-Methods'=> 'POST, GET, OPTIONS',
            'Access-Control-Allow-Headers'=> 'Content-Type, X-Auth-Token, Origin'
        ];
        if($request->getMethod() == "OPTIONS") {
            // The client-side application can set only headers allowed in Access-Control-Allow-Headers
            return Response::make('OK', 200, $headers);
        }



      if(config('applog.app_log'))
      {
        Log::info("Check on start ");

        $request->start_time=round(microtime(true) * 1000);
      }


      $response = $next($request);
        foreach($headers as $key => $value)
            $response->header($key, $value);



        return $response;
    }

}
