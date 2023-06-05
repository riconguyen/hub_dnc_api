<?php

  namespace App\Http\Middleware;

  use App\AppLog;
  use Closure;
  use Illuminate\Support\Facades\Log;

  class LogRequestAfter
  {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    function sensingData($string)
    {
      $pattern = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i';
      preg_match_all($pattern, $string, $matches);
      // var_dump($matches[0]);
      $arrReplace=[];
      if(count($matches[0]) > 0)

      {
        foreach($matches[0] as $item)
        {
          array_push($arrReplace, "**@**");
        }
      }

      $x= str_replace($matches[0],$arrReplace,$string); ;

      $patenPhone="/\(?([0-9]{3})\s*\)?\s*-?\s*([0-9]{3})\s*-?\s*([0-9]{4})/";
      preg_match_all($patenPhone, $x, $matches2);
      $arrReplace2=[];
      if(count($matches2[0]) > 0)

      {
        foreach($matches2[0] as $item)
        {
          array_push($arrReplace2, substr($item, 0, -4)."****");
        }
      }

      $x= str_replace($matches2[0],$arrReplace2,$x); ;



      return $x;
    }


    public function handle($request, Closure $next)
    {

      $response = $next($request);


      if(config('applog.app_log'))
      {
        $currentAction = \Route::currentRouteAction();
        $duration= round(microtime(true) * 1000)-  intval($request->start_time);
        list($controller, $method) = explode('@', $currentAction);



        if(!in_array($method, config('applog.whitelist_no_log_function')))
        {
          $appLog = new AppLog();
          $appLog->application_code = config('applog.application_code');
          $appLog->service_code = strtoupper($method);
          $appLog->thread_id = "";
          $appLog->request_id = "";
          $appLog->session_id = $request->bearerToken();
          $appLog->ip_port_parent_node = config('applog.ip_port_parent_node');
          $appLog->ip_port_current_node = config('applog.ip_port_current_node');
          $appLog->request_content = $this->sensingData(substr(json_encode($request->except("token", "password", "retype_password","new_password","hotline_numbers","hotline_number")), 0, 3999));
          $appLog->start_time = date("Y-m-d H:i:s", $request->start_time/1000);
          $appLog->duration = $duration;
          $appLog->action_name = strtoupper($method);

          $appLog->account = "";
          $appLog->thread_name = "";
          $appLog->source_class = $controller;
          $appLog->source_line = null;
          $appLog->source_method = $method;
          $appLog->client_request_id = "";
          $appLog->service_provider = "";
          $appLog->client_ip = $request->ip();
          $appLog->data_extend = '{"url":"'.$request->url().'"}';

          $appLog->end_time = date("Y-m-d H:i:s");
          $appLog->user_name =  $request->user ? substr($request->user->email, 0, -4)."****" : null;
          $appLog->transaction_status = $response->status() ==0;
          $appLog->error_code = $response->status();
          $appLog->error_description = $this->sensingData($response->status() != 200 ? substr(json_encode($response->original), 0, 999) : null);
          $appLog->response_content = $this->sensingData($response->status() != 200 ? substr(json_encode($response->original), 0, 999) : null);

          $appLog->save();
        }


        $currentAction = \Route::currentRouteAction();
        Log::info("Duration ON AFTER:".$duration. " Method: ".$currentAction);

      }


      // Perform action

      return $response;
    }
  }
