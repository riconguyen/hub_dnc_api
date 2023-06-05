<?php

namespace App\Http\Controllers;

use App\Activity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    // Hiển thị dữ liệu từ activity

  public function getLogs(Request $request)
  {
    $user= $request->user();

    $totalPerPage = $request->count?$request->count:20;
    // Valid data
    $page= $request->page?$request->page:1;
    $skip= ($page-1)*$totalPerPage;


    $validate = $request->validate([
      'page'=>'nullable|integer',
      'count'=>'nullable|integer',
      'q'=>'nullable|alpha_dash|max:50',
      'action'=>'nullable|alpha_dash|max:100'

    ]);



    $qsort= '-id';
    $sort=$qsort[0]=='-'?"DESC":"ASC";
    $sortCol= substr($qsort,1);
    $param=[];

    $strQ= $request->q;
    $action = $request->action;

    if (!$this->checkEntity($user->id, "VIEW_CHANGE_LOG")) {
      Log::info($user->email . '  TRY TO GET LogController.getLogs WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 400);
    }


    $sql="select a.*, u.name username, u.email from activity  a left join users u  on a.user_id = u.id where  1=1 ";
    $sqlCount="select count(*) total from activity  a left join users u  on a.user_id = u.id where  1=1 ";


    if($strQ)
    {
      $sql .= " AND (enterprise_number like ? or hotline_number like ?)";
      $sqlCount .= " AND (enterprise_number like ? or hotline_number like ?)";

      array_push($param, "%".$strQ."%", "%".$strQ."%");
    }

    if($action)
    {
      $sql .=" AND action like ?";
      $sqlCount .=" AND action like ?";
      array_push($param, config("sbc.action.".$action));
    }

    $sql .= " ORDER BY   " . $sortCol . "  " . $sort . "  LIMIT ?,?";


    $resCount= DB::select($sqlCount, $param);

    array_push($param, $skip, $totalPerPage);


    $res= DB::select($sql, $param);
    return response()->json(['status'=>true,'data'=>$res,'action'=>config("sbc.action"), 'count'=>$resCount[0]->total],200);


  }
}
