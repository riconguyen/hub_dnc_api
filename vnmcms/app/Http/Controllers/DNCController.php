<?php

namespace App\Http\Controllers;

use App\BlackList;
use App\Hotlines;
use App\HotlinesBackup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;

class DNCController extends Controller
{
    //
  public function postDNCBlacklist(Request $request) {
    //    sleep(1);

    $user= $request->user();
    Log::info("Request  postDNCBlacklist by ".$user->email);
    $data = request('data', []);
    $validator = Validator::make($request->only('data'), [

      'data'=>'required|array|max:501'
    ]);
    // Trả về lỗi nếu sai
    if ($validator->fails()) {

      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }

    foreach ($data as $item) {
      $blaclist = BlackList::where('phone_no', $item['phone_no'])->where("type",0)->first();
      if (!$blaclist) {
        $blaclist = new BlackList();
        $blaclist->phone_no = $item['phone_no'];
      }
      $blaclist->status = $item['status'];
      $blaclist->updated_at = date("Y-m-d H:i:s");
      $blaclist->save();
    }

    return ['status' => true, 'successCount' => count($data)];
  }

  public function getDNCBlacklist(Request $request)
  {
    $user= $request->user();
    Log::info("Request  getListDNCBlacklist by ".$user->email);

    $page=request("page",1);
    $limit= request('count', 20);
    $query=request('q',null);

//    $this->validate($request, [
//      'page'=>'nullable|numeric',
//      'count'=>'nullable|numeric|max:500',
//      'q'=>'nullable|alpha_numeric|max:25'
//    ]);

    $validator = Validator::make($request->only('q','count','page'), [
      'q' => 'nullable|alpha_spaces|max:50',
      'count' => 'sometimes|numeric|max:300',
      'page'=>'sometimes|numeric|max:1000'
    ]);
    // Trả về lỗi nếu sai
    if ($validator->fails()) {

      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }


    $start=($page-1)* $limit;

    $sql="select * from blacklist where  status=1 ";
    $sqlTotal="select count(*) total from blacklist where  status=1  ";
    $params=[];
    if($query)
    {
      $sql .= " and phone_no like ? ";
      $sqlTotal .= " and phone_no like ? ";

      array_push($params, "%$query%");
    }

    $total= DB::select($sqlTotal, $params)[0]->total;

    $sql .= " order by updated_at desc limit ?,?";
    array_push($params, $start,$limit);

    $res= DB::select($sql, $params);

    return response()->json(['status'=>true,'data'=>$res, 'count'=>$total],200);

  }

  public function getDNCWhiteList(Request $request)
  {
    $user= $request->user();
    Log::info("Request  getListDNCBlacklist by ".$user->email);

    $page=request("page",1);
    $limit= request('count', 20);
    $query=request('q',null);

//    $this->validate($request, [
//      'page'=>'nullable|numeric',
//      'count'=>'nullable|numeric|max:500',
//      'q'=>'nullable|alpha_numeric|max:25'
//    ]);

    $validator = Validator::make($request->only('q','count','page'), [
      'q' => 'nullable|alpha_spaces|max:50',
      'count' => 'sometimes|numeric|max:300',
      'page'=>'sometimes|numeric|max:1000'
    ]);
    // Trả về lỗi nếu sai
    if ($validator->fails()) {

      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }


    $start=($page-1)* $limit;

    $sql="select hlc.*, c.enterprise_number, c.companyname from hot_line_config hlc join customers  c on c.id= hlc.cus_id  where  status in (0,1)   and whitelist_nd91=1  ";
    $sqlTotal="select count(*) total  from hot_line_config hlc join customers  c on c.id= hlc.cus_id    where  status in (0,1)  and whitelist_nd91=1   ";
    $params=[];
    if($query)
    {
      $sql .= "  and ( hotline_number like ?  or hlc.enterprise_number= ?) ";
      $sqlTotal .= " and ( hotline_number like ?  or hlc.enterprise_number= ?) ";

      array_push($params, "%$query%", "$query");
    }

    $total= DB::select($sqlTotal, $params)[0]->total;

    $sql .= " order by hlc.updated_at desc limit ?,?";
    array_push($params, $start,$limit);

    $res= DB::select($sql, $params);

    return response()->json(['status'=>true,'data'=>$res, 'count'=>$total],200);

  }

  public function postDeactiveDNCBlacklist(Request $request)
  {
    $user= $request->user();
    Log::info("Request  postDeactiveDNCBlacklist by ".$user->email);


    $validator = Validator::make($request->only('id'), [
      'id' => 'required|int',
    ]);
    // Trả về lỗi nếu sai
    if ($validator->fails()) {

      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }


    $updateDNC= BlackList::where('id',request('id'))
      ->where('status',1)
      ->first();
    if($updateDNC)
    {
    $updateDNC->status=0;
      $updateDNC->updated_at = date("y-m-d H:i:s");
      $updateDNC->save();
    }
    else
    {
      return response()->json(['status'=>false,'data'=>null, 'message'=>'NOT_FOUND_ACTIVE_PHONE_NO'],422);

    }
    return response()->json(['status'=>true],200);

  }

  public function postDNCWhitelist(Request $request) {
    //    sleep(1);

    $user = $request->user();
    Log::info("Request  postDNCWhitelist by " . $user->email);
    $data = request('data', []);
    $validator = Validator::make($request->only('hotline_numbers', 'white_list'), [

      'hotline_numbers' => 'required|number_dash|max:1500', 'white_list' => 'required|in:0,1']);
    // Trả về lỗi nếu sai
    if ($validator->fails()) {
      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }

    $whitelist = request('white_list', 0);

    if (strpos($request->hotline_numbers, ',') !== false) {
      $hotlineNumbers = explode(',', $request->hotline_numbers);
    } else {
      $hotlineNumbers = [$request->hotline_numbers];
    }

    // Validate if is Hotline Number

    $lstErrors = [];

    foreach ($hotlineNumbers as $number) {
      $re = '/^0[0-9]{8,11}$/m';

      if (preg_match($re, $number, $matches, PREG_OFFSET_CAPTURE, 0)) {
      } else {
        array_push($lstErrors, $number);
      }
    }

    if (count($lstErrors) > 0) {
      return $this->ApiReturn(["hotline_numbers" => ["Hotline numbers is invalid"]], false, 'The given data was invalid: ' . implode(",", $lstErrors), 422);
    }

    $BACKUPSTATE = $request->single_mode == 1 ? false : config("server.backup_site");

    DB::beginTransaction();
    if ($BACKUPSTATE) {
      DB::connection("db2")->beginTransaction();
    }

    try {
      Hotlines::whereIn('hotline_number', $hotlineNumbers)->whereIn('status', [0, 1])->update(['whitelist_nd91' => $whitelist]);

      if ($BACKUPSTATE) {
        HotlinesBackup::whereIn('hotline_number', $hotlineNumbers)->whereIn('status', [0, 1])->update(['whitelist_nd91' => $whitelist]);
      }

      DB::commit();
      if ($BACKUPSTATE) {
        DB::connection("db2")->commit();
      }
      return ['status' => true];
    } catch (\Exception $exception) {
      DB::rollback();
      if ($BACKUPSTATE) {
        DB::connection("db2")->rollback();
      }
      Log::info($exception->getTraceAsString());

      return response()->json(['status' => false, 'message' => "Internal server error"], 500);
    }
  }



}
