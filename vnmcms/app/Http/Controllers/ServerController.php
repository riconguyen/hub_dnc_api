<?php

namespace App\Http\Controllers;


use App\ApiServers;
use App\Customers;
use App\CustomersBackup;
use App\Hotlines;
use App\ServiceConfig;
use Illuminate\Support\Facades\Log;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ServerController extends Controller
{
    //


  public function getList(Request $request)
  {
    $user=$request->user();
    if ($user->role != ROLE_ADMIN) {
      return ['error' => 'Permission denied'];
    }

    $res= ApiServers::all();
    return response()->json(['data'=>$res, 'status'=>true],200);

  }

  public function postServer(Request $request)
  {

    $user=$request->user();
    if ($user->role != ROLE_ADMIN) {
      return ['error' => 'Permission denied'];
    }


    $server_id= $request->id;
    if($server_id)
    {
      $server= ApiServers::find($server_id);


    }
    else
    {
      $server= new ApiServers();
    }

    $server->server_name= $request->server_name;
    $server->ip= $request->ip;
    $server->port= $request->port;
    $server->api_url= $request->api_url;
    $server->server_type= $request->server_type;
    $server->save();
    return response()->json(['data'=>$server, 'status'=>true],200);
  }



  public function postActiveServer(Request $request)
  {

    $user=$request->user();
    if ($user->role != ROLE_ADMIN) {
      return ['error' => 'Permission denied'];
    }


    $server_id= $request->id;
    if($server_id) {
      $lstUpdate=[];

      $previousServer= ApiServers::where('active',1)->get();
      if(count($previousServer)>0)
      {
        foreach ($previousServer as $item)

        {
          $previous= ApiServers::find($item->id);
          $previous->active=0;
          $previous->save();

          array_push($lstUpdate, $previous);
        }

      }



      $server = ApiServers::find($server_id);

      $server->active =1;

      $server->save();

      return response()->json(['activeServer' => $server,'deActiveServer'=>$lstUpdate, 'status' => true], 200);
    }
    else
    {
      return response()->json(['msg' => 'Not found server id', 'status' => false], 500);
    }

  }

  public function getActiveServer(Request $request) {
    $user = $request->user();
    if ($user->role != ROLE_ADMIN) {
      return ['error' => 'Permission denied'];
    }

    try {
      $server = ApiServers::where('active', 1)->select('api_url as webservice_url', 'server_name')->first();
    } catch (\Exception $exception) {
      return $this->ApiReturn([], false, "Server error", 500);
    }

    return $this->ApiReturn($server, true, null, 200);
  }


  public function  postServerResource(Request $request)
  {
    $startTime=round(microtime(true) * 1000);
    $user = $request->user();
    if ($user->role != ROLE_ADMIN) {

      return response()->json(['error' => 'Permission denied'], 403);
    }


      try {
        $process = new Process('python E:\2019\S-CONNECT\VCONNECT\vconnect\WEB\public\cpuResource.py');
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
          throw new ProcessFailedException($process);
        }

        $logDuration=round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|PYTHON_TRACE|".$logDuration."|Python tracking success");


        return $process->getOutput();
        //  return $id;
      }
      catch (Exception $exception)
      {
        return $exception;
      }





  }



}
