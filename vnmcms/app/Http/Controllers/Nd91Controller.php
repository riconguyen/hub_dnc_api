<?php

namespace App\Http\Controllers;

use App\Nd91CrdReport;
use App\Nd91DncConfig;
use App\Nd91Quota;
use App\Nd91QuotaConfig;
use App\Nd91TimeRangeConfig;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use  Validator;

class Nd91Controller extends Controller
{

  public function initNd91Config(Request $request)
  {

    $user= $request->user();

    if (!$this->checkEntity($user->id, "ND91_CONFIG")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.initNd91Config WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $getConfig= Nd91DncConfig::all();

    return response()->json(['list'=>$getConfig, 'status'=>true],200);

  }

  public function postSaveDncConfig(Request $request)
  {

    $user= $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ND91_CONFIG")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.initNd91Config WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $this->validate($request, [
      "config_key"=>"required|sql_char|max:40",
      "active"=>"required|in:0,1",

    ]);


    $dncConfig= Nd91DncConfig::where("config_key", $request->config_key)->first();
    if(!$dncConfig)
    {
      return response()->json(['status'=>false, 'message'=>'Bad request, not found config key '],403);
    }


    $dncConfig->active= request("active",0);
//    $dncConfig->apply_rule= request("apply_rule",1);
    $dncConfig->save();




    $logDuration = round(microtime(true) * 1000) - $startTime;
    Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "
    |ND91_DNC_CONFIG|" . $logDuration . "|ND91_DNC_CONFIG_SUCCESS");




    return response()->json(['status'=>true, 'config'=>$dncConfig],200);




  }


  public function initNd91TimeRange(Request $request)
  {

    $user= $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ND91_CONFIG")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.initNd91TimeRange WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $getConfig= Nd91TimeRangeConfig::all();

    return response()->json(['list'=>$getConfig, 'status'=>true],200);
  }


  public function initNd91Quota(Request $request)
  {
    $user= $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ND91_CONFIG")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.initNd91Quota WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }



    return response()->json(['list'=>Nd91QuotaConfig::all(), 'status'=>true],200);

  }




  public function postSaveQuotaConfigItem(Request $request) {
    $user = $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ND91_CONFIG")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.postSaveQuotaConfig WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }


    $id = $request->id;
    $configId = $request->config_id;

    $this->validate($request, ['id' => "required|int|max:1000", 'max_call_per_month' => 'required|int',
      'max_call_per_day' => 'required|int']);

    $quotaConfig = Nd91QuotaConfig::where("id", $id)->first();
    if (!$quotaConfig) {
      return response()->json(['status' => false, 'message' => 'Bad request, not found config key '], 403);
    }

    $quotaConfig->max_call_per_day = request("max_call_per_day", 0);
    $quotaConfig->max_call_per_month = request("max_call_per_month", 0);

    $quotaConfig->save();


    $logDuration = round(microtime(true) * 1000) - $startTime;
    Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ND91_SAVE_QUOTA_ITEM|" . $logDuration . "|ND91_SAVE_QUOTA_ITEM_SUCCESS");



    return response()->json(['status' => true, 'config' => $quotaConfig], 200);
  }


  public function postSaveTimeRangeConfig(Request $request)
  {
    $user = $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ND91_CONFIG")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.postSaveQuotaConfig WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $timeEdit=request('time_edit', false);



    $this->validate($request, [
    "id"=>"required|int",
      "time_allow"=>"nullable|max:50",
      "name"=>'nullable|max:50',
      'description'=>'nullable|max:50',
      "active"=>'nullable|in:0,1'
    ]);

    $timeRange= Nd91TimeRangeConfig::where('id',$request->id)->first();

    if(!$timeRange)
    {
      return response()->json(['status' => false, 'message' => 'Bad request, not found config key '], 400);

    }
    if($timeEdit)
    {

      $timeRange->name= request('name','');
      $timeRange->description= request('description','');
      $timeRange->time_allow= request('time_allow','');
    }
    else
    {
      $timeRange->active= request('active',0);

    }

    $timeRange->save();



    $logDuration = round(microtime(true) * 1000) - $startTime;
    Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ND91_SAVE_TIME_RANGE_CONFIG|" . $logDuration . "|ND91_SAVE_TIME_RANGE_CONFIG_SUCCESS");

    return response()->json(['status' => true], 200);
  }

  public  function getReportBK(Request $request)
  {

    $user = $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ND91_REPORT")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.getReport WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validate = $request->only("start_date", "end_date","hotline");

    $validator = Validator::make($validate, ['start_date' => 'nullable|date|before:tomorrow',
      'end_date' => 'nullable|date|before:tomorrow|after:start_date',
      'hotline'=>'nullable|max:50'

    ]);
    // Trả về lỗi nếu sai dữ liệu đầu vào
    if ($validator->fails()) {
      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }
    // Kiểm tra trùng dữ liệu

    $startDate = $request->start_date ? date("Y-m-d 00:00:00", strtotime($request->start_date)) : date("Y-m-01 H:i:s");
    $endDate = $request->end_date ? date("Y-m-d 23:59:59", strtotime($request->end_date)) : date("Y-m-d H:i:s");

    if ($startDate < date("Y-m-d H:i:s", strtotime("-12 month"))) {
      return $this->ApiReturn(["start_date" => ["No longer than 12 months from now "]], false, 'The given data was invalid', 422);
    }

    if ($endDate < date("Y-m-d H:i:s", strtotime("-12 month"))) {
      return $this->ApiReturn(["end_date" => ["No longer than 12 months from now or newer than this time"]], false, 'The given data was invalid', 422);
    }




    $start = strtotime($startDate);
    $end = strtotime($endDate);

    $days_between = ceil(abs($end - $start) / 86400);

    if($days_between > 60)
    {
      return $this->ApiReturn(["end_date" => ["Report limit 90 days in range "]], false, 'The given data was invalid', 422);

    }

    $paramSuccess=[$startDate, $endDate];


    $arrayDays = [];

    for ($i = 0; $i < $days_between; $i++) {
      $nextDay = date('Y-m-d', strtotime($startDate . ' + ' . $i . ' days'));
      array_push($arrayDays, $nextDay);
    }


    $sql="select 
ifnull(sum(success),0) as success,
ifnull(sum(dnc+c197+quota+cm+qltttb+time_range),0) as nd91,
ifnull(sum(dnc),0) as dnc, 
ifnull(sum(c197),0) as c197,
ifnull(sum(quota),0) as quota, 
ifnull(sum(cm),0) as cm, 
ifnull(sum(qltttb),0) as qltttb,
ifnull(sum(time_range),0) as time_range,
ifnull(sum(other),0) as other,
DATE(report_date) setup_time
 from sbc.nd91_cdr_reports cr
 where report_date between  ? AND ? 
 ";

    $hotline= request('hotline',null);
    if($hotline)
    {
      $sql .=" and brand_name like ? or CLI=?";
      array_push($paramSuccess, "%$hotline%", $hotline);
    }


    $success= DB::select($sql."  ", $paramSuccess);
    $successChart= DB::select($sql."  group by DATE(report_date) ", $paramSuccess);


    $successChartObj=new \stdClass();
    $failChartObj=new \stdClass();
    foreach ($successChart as $item)
    {
      $successChartObj->{$item->setup_time}= $item;

      $failChartObj->{$item->setup_time}= $item;
    }


    $arrSuccessChart=[];
    $arrFailChart=[];
    foreach ($arrayDays as $day) {
      array_push($arrSuccessChart, ['full_time' => $day, 'total' => isset($successChartObj->{$day}) ? $successChartObj->{$day}->success : 0]);
      array_push($arrFailChart, ['full_time' => $day,
          'total' => isset($failChartObj->{$day}) ? $failChartObj->{$day}->nd91 : 0,
          'dnc' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->dnc) ? $failChartObj->{$day}->dnc : 0,
          'c197' => isset($failChartObj->{$day}) && isset($failChartObj->{$day}->dnc)? $failChartObj->{$day}->c197 : 0,
          'quota' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->c197) ? $failChartObj->{$day}->quota : 0,
          'cm' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->cm) ? $failChartObj->{$day}->cm : 0,
          'qltttb' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->qltttb) ? $failChartObj->{$day}->qltttb : 0,
          'time_range' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->time_range) ? $failChartObj->{$day}->time_range : 0

        ]
      );
    }

    return response()->json(['status' => true, 'report' => ['result' => $success[0], ], 'chart' => ['success' => $arrSuccessChart, 'fail' => $arrFailChart], 'd' => $days_between, 'range_of_day' => $arrayDays

    ], 200);



  }

  public  function getReport(Request $request)
  {
    $user = $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ND91_REPORT")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.getReport WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validate = $request->only("start_date", "end_date","hotline");

    $validator = Validator::make($validate, ['start_date' => 'nullable|date|before:tomorrow',
      'end_date' => 'nullable|date|before:tomorrow|after:start_date',
      'hotline'=>'nullable|max:50'

    ]);
    // Trả về lỗi nếu sai dữ liệu đầu vào
    if ($validator->fails()) {
      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }
    // Kiểm tra trùng dữ liệu

    $startDate = $request->start_date ? date("Y-m-d 00:00:00", strtotime($request->start_date)) : date("Y-m-01 H:i:s");
    $endDate = $request->end_date ? date("Y-m-d 23:59:59", strtotime($request->end_date)) : date("Y-m-d H:i:s");

    if ($startDate < date("Y-m-d H:i:s", strtotime("-12 month"))) {
      return $this->ApiReturn(["start_date" => ["No longer than 12 months from now "]], false, 'The given data was invalid', 422);
    }

    if ($endDate < date("Y-m-d H:i:s", strtotime("-12 month"))) {
      return $this->ApiReturn(["end_date" => ["No longer than 12 months from now or newer than this time"]], false, 'The given data was invalid', 422);
    }




    $start = strtotime($startDate);
    $end = strtotime($endDate);

    $days_between = ceil(abs($end - $start) / 86400);

    if($days_between > 90)
    {
      return $this->ApiReturn(["end_date" => ["Report limit 90 days in range "]], false, 'The given data was invalid', 422);

    }






    $sqlSuccess="select  IFNULL(count(*),0) total, DATE(x.created_at) setup_time
                  from  sbc.cdr_vendors_extention x  force index (created_at)                
                   where x.call_brandname= 1 and 
                  x.created_at  between ? and ?  ";

    $paramSuccess=[$startDate, $endDate];



    $sqlFail= "select DATE(v.created_at) setup_time, 
sum(case when (
v.reject_cause IN (?,?,?,?,?,?) ) then 1 else 0 end) total, 
sum(case when v.reject_cause = ? then 1 else 0 end) dnc,   
sum(case when v.reject_cause = ? then 1 else 0 end) c197,  
sum(case when v.reject_cause = ? then 1 else 0 end) quota,   
sum(case when v.reject_cause = ? then 1 else 0 end) cm,  
sum(case when v.reject_cause = ? then 1 else 0 end) qltttb,  
sum(case when v.reject_cause = ? then 1 else 0 end) time_range
 from 
 
 sbc.cdr_vendors_failed_extention v  
    WHERE  v.call_brandname= 1  AND v.created_at  BETWEEN ? AND ? ";


    $paramFail=[
      config("nd91.dnc"),
      config("nd91.c197"),
      config("nd91.quota"),
      config("nd91.cm"),
      config("nd91.qltttb"),
      config("nd91.time_range"),


      config("nd91.dnc"),
      config("nd91.c197"),
      config("nd91.quota"),
      config("nd91.cm"),
      config("nd91.qltttb"),
      config("nd91.time_range"),

      $startDate,
      $endDate
    ];



    if($hotline= request("hotline",null))
    {
      $sqlSuccess="select  IFNULL(count(*),0) total, DATE(x.created_at) setup_time
                  from  sbc.cdr_vendors_extention x 
                   join sbc.cdr_vendors c on x.call_id= c.call_id and   x.call_brandname =1
                    join hot_line_config hlc on c.CLI= hlc.hotline_number
                   where
                  x.created_at  between ? and ?  AND hlc.status in(0,1)   AND  (CLI like ? or brand_name like ? ) ";


      $sqlFail= "select DATE(v.created_at) setup_time, x.CLI, 
                  sum(case when (
                  v.reject_cause IN (?,?,?,?,?,?) ) then 1 else 0 end) total, 
                  sum(case when v.reject_cause = ? then 1 else 0 end) dnc,   
                  sum(case when v.reject_cause = ? then 1 else 0 end) c197,  
                  sum(case when v.reject_cause = ? then 1 else 0 end) quota,   
                  sum(case when v.reject_cause = ? then 1 else 0 end) cm,  
                  sum(case when v.reject_cause = ? then 1 else 0 end) qltttb,  
                  sum(case when v.reject_cause = ? then 1 else 0 end) time_range
                  from 
                  sbc.cdr_vendors_failed x 
                  join sbc.cdr_vendors_failed_extention v on v.call_id= x.call_id AND v.call_brandname= 1 
                        join hot_line_config hlc on x.CLI= hlc.hotline_number
                  WHERE v.created_at BETWEEN ? AND ?  AND hlc.status in(0,1) AND (CLI like ? or brand_name like ? ) ";

      array_push($paramFail,"%$hotline%","%$hotline%");
      array_push($paramSuccess,"%$hotline%","%$hotline%");

    }


    $resSuccesAll= DB::select($sqlSuccess, $paramSuccess);
    $resFailAll= DB::select($sqlFail, $paramFail);


    $arrayDays = [];

    for ($i = 0; $i < $days_between; $i++) {
      $nextDay = date('Y-m-d', strtotime($startDate . ' + ' . $i . ' days'));
      array_push($arrayDays, $nextDay);
    }


    $successChart= DB::select($sqlSuccess." group by DATE(setup_time)", $paramSuccess);
    $failChart= DB::select($sqlFail." group by DATE(setup_time)", $paramFail);

    $successChartObj=new \stdClass();
    $failChartObj=new \stdClass();
    foreach ($successChart as $item)
    {
      $successChartObj->{$item->setup_time}= $item;
    }

    foreach ($failChart as $item)
    {
      $failChartObj->{$item->setup_time}= $item;
    }


    $arrSuccessChart=[];
    $arrFailChart=[];
      foreach ($arrayDays as $day) {
        array_push($arrSuccessChart, ['full_time' => $day, 'total' => isset($successChartObj->{$day}) ? $successChartObj->{$day}->total : 0]);
        array_push($arrFailChart, ['full_time' => $day,
          'total' => isset($failChartObj->{$day}) ? $failChartObj->{$day}->total : 0,
          'dnc' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->dnc) ? $failChartObj->{$day}->dnc : 0,
          'c197' => isset($failChartObj->{$day}) && isset($failChartObj->{$day}->dnc)? $failChartObj->{$day}->c197 : 0,
          'quota' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->c197) ? $failChartObj->{$day}->quota : 0,
          'cm' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->cm) ? $failChartObj->{$day}->cm : 0,
          'qltttb' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->qltttb) ? $failChartObj->{$day}->qltttb : 0,
          'time_range' => isset($failChartObj->{$day})&& isset($failChartObj->{$day}->time_range) ? $failChartObj->{$day}->time_range : 0

          ]
        );
      }




    return response()->json(['status' => true, 'checkV'=>'2022','report' => ['successTotal' => $resSuccesAll[0], 'failTotal' => $resFailAll[0],], 'chart' => ['success' => $arrSuccessChart, 'fail' => $arrFailChart], 'd' => $days_between, 'range_of_day' => $arrayDays

    ], 200);
  }

  public function getReportBrandName(Request $request)
  {

    $user = $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ND91_REPORT")) {
      Log::info($user->email . '  TRY TO GET Nd91Controller.getReport WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validate = $request->only("start_date", "end_date","hotline");

    $validator = Validator::make($validate, ['start_date' => 'nullable|date|before:tomorrow',
      'end_date' => 'nullable|date|before:tomorrow|after:start_date',
      'hotline'=>'nullable|max:250'

    ]);
    // Trả về lỗi nếu sai dữ liệu đầu vào
    if ($validator->fails()) {
      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }
    // Kiểm tra trùng dữ liệu

    $startDate = $request->start_date ? date("Y-m-d 00:00:00", strtotime($request->start_date)) : date("Y-m-01 H:i:s");
    $endDate = $request->end_date ? date("Y-m-d 23:59:59", strtotime($request->end_date)) : date("Y-m-d H:i:s");

    if ($startDate < date("Y-m-d H:i:s", strtotime("-12 month"))) {
      return $this->ApiReturn(["start_date" => ["No longer than 12 months from now "]], false, 'The given data was invalid', 422);
    }

    if ($endDate < date("Y-m-d H:i:s", strtotime("-12 month"))) {
      return $this->ApiReturn(["end_date" => ["No longer than 12 months from now or newer than this time"]], false, 'The given data was invalid', 422);
    }




    $start = strtotime($startDate);
    $end = strtotime($endDate);

    $days_between = ceil(abs($end - $start) / 86400);

    if($days_between > 90)
    {
      return $this->ApiReturn(["end_date" => ["Report limit 90 days in range "]], false, 'The given data was invalid', 422);

    }


    $paramFail=[
      config("nd91.dnc"),
      config("nd91.c197"),
      config("nd91.quota"),
      config("nd91.cm"),
      config("nd91.qltttb"),
      config("nd91.time_range"),

      config("nd91.dnc"),
      config("nd91.c197"),
      config("nd91.quota"),
      config("nd91.cm"),
      config("nd91.qltttb"),
      config("nd91.time_range"),

      $startDate,
      $endDate
    ];

    $totalPerPage=request('count',10);
    $query= null;
    $page= request('page',1);
    $skip= ($page-1)*$totalPerPage;



    $sqlFail= "select IFNULL(hlc.brand_name,'--') brand_name,   x.CLI, 
                  sum(case when (
                  v.reject_cause  IN (?,?,?,?,?,?)
                  ) then 1 else 0 end) total, 
                  sum(case when v.reject_cause = ? then 1 else 0 end) dnc,   
                  sum(case when v.reject_cause = ? then 1 else 0 end) c197,  
                  sum(case when v.reject_cause = ? then 1 else 0 end) quota,   
                  sum(case when v.reject_cause = ? then 1 else 0 end) cm,  
                  sum(case when v.reject_cause = ? then 1 else 0 end) qltttb,  
                  sum(case when v.reject_cause = ? then 1 else 0 end) time_range                
                  from 
                  sbc.cdr_vendors_failed x 
                  join sbc.cdr_vendors_failed_extention v on v.call_id= x.call_id 
                        join hot_line_config hlc on x.CLI= hlc.hotline_number
                  WHERE  v.call_brandname= 1 AND  v.created_at BETWEEN ? AND ? AND hlc.status in(0,1)   ";






    if($hotline= request("hotline",null))
    {

      $sqlFail .=" AND (hlc.brand_name like ? OR hlc.hotline_number = ?)";
      array_push($paramFail,"%$hotline%","$hotline");

    }



    $sqlFail .= " GROUP BY hlc.brand_name  order by total DESC";

    $sqlFailCount = "SELECT count(*)  total from ($sqlFail) a";

    $resFailCount = DB::select($sqlFailCount, $paramFail);

    $sqlFail .= " LIMIT ?,? ";
    array_push($paramFail, $skip, $totalPerPage);

    $resFail = DB::select($sqlFail, $paramFail);



    $i=1;
    foreach ($resFail as $item)
    {
      $item->index= $i;
      $i++;
      }

    // Build the query

    return response()->json(['status'=>true,'data'=>$resFail,'count'=>$resFailCount[0]->total],200);
  }

  public function synNd91Report(Request $request)
  {
    $startDate = $request->start_date ? date("Y-m-d 00:00:00", strtotime($request->start_date)) : date("Y-m-01 H:i:s");
    $endDate = $request->end_date ? date("Y-m-d 23:59:59", strtotime($request->end_date)) : date("Y-m-d H:i:s");

    if ($startDate < date("Y-m-d H:i:s", strtotime("-12 month"))) {
      return $this->ApiReturn(["start_date" => ["No longer than 12 months from now "]], false, 'The given data was invalid', 422);
    }

    if ($endDate < date("Y-m-d H:i:s", strtotime("-12 month"))) {
      return $this->ApiReturn(["end_date" => ["No longer than 12 months from now or newer than this time"]], false, 'The given data was invalid', 422);
    }




    $start = strtotime($startDate);
    $end = strtotime($endDate);

    $days_between = ceil(abs($end - $start) / 86400);

    if($days_between > 90)
    {
      return $this->ApiReturn(["end_date" => ["Report limit 90 days in range "]], false, 'The given data was invalid', 422);

    }

    for ($i = 0; $i < $days_between; $i++) {
      $nextDay = date('Y-m-d', strtotime($startDate . ' + ' . $i . ' days'));
      $this->synNd91Report2($nextDay);
    }

    return [];


  }
  public function synNd91Report2($rday)
  {

      $successSQL = "
  select 
  hlc.enterprise_number, CLI, hlc.brand_name,hlc.cus_id,
  count(1) success
   from  sbc.cdr_vendors 
    join sbc.cdr_vendors_extention on cdr_vendors.call_id=cdr_vendors_extention.call_id 
    left join vnmcms.hot_line_config hlc on hlc.hotline_number= cdr_vendors.CLI
    
    where DATE(setup_time)= ? group by CLI
    
    ";

      $failSql="select 
hlc.enterprise_number, CLI, hlc.brand_name,hlc.cus_id,
sum(case when reject_cause = 501 then 1 else 0 end) dnc,   
sum(case when reject_cause = 502 then 1 else 0 end) c197,  
sum(case when  reject_cause = 503 then 1 else 0 end) cm,   
sum(case when reject_cause = 504 then 1 else 0 end) quota ,  
sum(case when  reject_cause = 505 then 1 else 0 end) time_range,  
sum(case when  reject_cause = 506 then 1 else 0 end) qltttb,
sum(case when  (reject_cause !=501  AND  reject_cause !=502  AND  reject_cause !=503  AND  reject_cause !=504 AND  reject_cause !=505 AND  reject_cause !=506) then 1 else 0 end) other 
 from  sbc.cdr_vendors_failed 
  join sbc.cdr_vendors_failed_extention on cdr_vendors_failed.call_id=cdr_vendors_failed_extention.call_id 
  left join vnmcms.hot_line_config hlc on hlc.hotline_number= cdr_vendors_failed.CLI
  
  where DATE(setup_time)= ? group by CLI
  
  ";


      $param=[$rday];

      $resSuccess= DB::select($successSQL, $param);
      $resFail= DB::select($failSql, $param);

      $obj= new \stdClass();

      foreach ($resFail as $item)
      {
        $obj->{$item->enterprise_number.$item->CLI}= $item;
        $obj->{$item->enterprise_number.$item->CLI}->success= 0;
        $obj->{$item->enterprise_number.$item->CLI}->report_date= $rday;
      }

      foreach ($resSuccess as $item2)
      {
        if(isset($obj->{$item2->enterprise_number.$item2->CLI}))
        {
          $obj->{$item2->enterprise_number.$item2->CLI}->success= $item2->success;
        }
        else
        {
          $obj->{$item2->enterprise_number.$item2->CLI}= $item2;
          $obj->{$item2->enterprise_number.$item2->CLI}->dnc= 0;
          $obj->{$item2->enterprise_number.$item2->CLI}->c197=  0;
          $obj->{$item2->enterprise_number.$item2->CLI}->cm= 0;
          $obj->{$item2->enterprise_number.$item2->CLI}->quota= 0;
          $obj->{$item2->enterprise_number.$item2->CLI}->quota= 0;
          $obj->{$item2->enterprise_number.$item2->CLI}->time_range= 0;
          $obj->{$item2->enterprise_number.$item2->CLI}->qltttb=  0;
          $obj->{$item2->enterprise_number.$item2->CLI}->other=  0;
          $obj->{$item2->enterprise_number.$item2->CLI}->report_date= $rday;



        }
      }

      $lst=[];

      foreach ($obj as $key=>$value)
      {
        array_push($lst, $value);
      }


      foreach ($lst as $item)
      {
        $Nd91Cdr= Nd91CrdReport::where('CLI', $item->CLI)->where('enterprise_number', $item->enterprise_number)->where('report_date',$item->report_date)->first();
        if($Nd91Cdr)
        {
          // update data

        }
        else
        {
          $Nd91Cdr= new  Nd91CrdReport();
          $Nd91Cdr->report_date= $item->report_date;
          $Nd91Cdr->enterprise_number= $item->enterprise_number;
          $Nd91Cdr->CLI= $item->CLI;
          $Nd91Cdr->brand_name= $item->brand_name;
          $Nd91Cdr->cus_id= $item->cus_id;
        }

        $Nd91Cdr->success= $item->success;
        $Nd91Cdr->dnc= $item->dnc;
        $Nd91Cdr->c197= $item->c197;
        $Nd91Cdr->cm= $item->cm;
        $Nd91Cdr->quota= $item->quota;
        $Nd91Cdr->quota= $item->quota;
        $Nd91Cdr->time_range= $item->time_range;
        $Nd91Cdr->qltttb= $item->qltttb;
        $Nd91Cdr->other= $item->other;


        $Nd91Cdr->save();
      }

      return ["reportToday"=>$lst];
  }





}
