<?php
namespace App\Http\Controllers;

use App\ChargeFeeLimit;
use App\Customers;
use App\CustomersBackup;
use App\FeeLimitLog;
use App\Hotlines;
use App\QuantitySubcriberBackup;
use App\TosServices;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ActivityController;


class AccountController extends Controller
{

    function  filterDataEscape($data)
    {



        $whiteSpace = '\s';  //if you dnt even want to allow white-space set it to ''
        $pattern = '/[^a-zA-Z0-9'  . $whiteSpace . ']/u';
        return  preg_replace($pattern, '', (string) $data);



    }


    private function Activity($activity, $table, $dataID, $rootid, $action)
    {
        $active = new ActivityController;
        $active->AddActivity($activity, $table, $dataID, $rootid, $action);
    }

    private function synCustomerToServiceSub($data)
    {
        $begin_charge_date = date_create(date('Y-m-d H:i:s'));
        date_modify($begin_charge_date, "+1 day");
        $num_of_agent = 0;
        $resExist = DB::table('service_subcriber as a')
            ->join('customers as b', 'a.id', '=', 'b.id')
            ->where('a.id', $data['cus_id'])
            ->select('a.enterprise_number as enterprise_number')
            ->first();
        $postData = [
            "service_config_id" => $data["service_id"],
            "enterprise_number" => $data["enterprise_number"],
            "status" => $data["blocked"],
            "id" => $data["cus_id"],
            "num_agent" => $num_of_agent,
            "updated_at" => date('Y-m-d H:i:s'),
            "begin_charge_date" => $begin_charge_date
        ];
        if (count($resExist) > 0) {
            // Check extist data
            unset($postData["num_agent"]);
            unset($postData["begin_charge_date"]);
            DB::table('service_subcriber')
                ->where('id', $data["cus_id"])
                ->update($postData);
            $this->Activity($postData, "enterprise_number", $data["cus_id"], 0, "Update");
            // UPDATE OTHER TABLE HERE
            DB::table('hot_line_config')
                ->where('id', $data["cus_id"])
                ->update(['enterprise_number' => $data['enterprise_number'], 'updated_at' => $postData['updated_at']]);
            $this->Activity(['enterprise_number' => $data['enterprise_number'], 'updated_at' => $postData['updated_at']], "hotline_config", 0, $data["cus_id"], "Update");
        } else {
            $subid = DB::table('service_subcriber')
                ->insert($postData);
            $this->Activity($postData, "service_subcriber", $data["cus_id"], 0, "Created");
        }
        return true;
    }

    //
    public function getList(Request $request)
    {
        $user= $request->user;
        if($user->role != ROLE_ADMIN && $user->role !=ROLE_USER && $user->role !=ROLE_BILLING)
        {
            return ['error'=>'Permission denied'];
        }

        $page = 0;
        $take =25;
        $errors = Validator::make($request->only('query','page', 'take'), [
                'query' => 'sometimes|max:50',
                'page' => 'sometimes|integer|min:0',
                'take'=>'sometimes|integer|min:0',

            ]
        );
        if ($errors->fails()) {
            return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
        }



        if ($request->page) {
            $page = $request->page;
        }
        if ($request->take) {
            $take = $request->take;
        }
        if ($request->query) {
            $query = $request->input('query');
        } else {
            $query = null;
        }
        $skip = ($page - 1) * $take;

        if ($query) {
            $totalCustomer = DB::table('customers')
                ->whereRaw('companyname like ? OR enterprise_number like ? or email like ? or taxcode like ? ',
                  ["%$query%","%$query%","%$query%","%$query%"])
                ->count();
            $res = DB::table('customers AS a')
                ->leftJoin('service_config As b', 'a.service_id', '=', 'b.id');
                if($user->role==ROLE_BILLING)
                {
                    $res->where('a.account_id', $user->id);
                }


                $res->whereRaw('a.companyname like ? OR a.enterprise_number like ? or a.email like ? or a.taxcode like ? ',
                  ["%$query%","%$query%","%$query%","%$query%"])
                ->select('a.*', 'b.service_name')
                ->groupBy("a.id")
                ->orderBy("a.id", 'DESC')

                ->take($take)
                ->skip($skip)
                ->get();
        } else {
            $totalCustomer = DB::table('customers')
                ->count();
            $res = DB::table('customers AS a')
                ->leftJoin('service_config As b', 'a.service_id', '=', 'b.id')

                ->select('a.*', 'b.service_name')
                ->groupBy("a.id")
                ->orderBy("a.id", 'DESC')
                ->take($take)
                ->skip($skip)
                ->get();
        }




        return response()->json(['status'=>true, 'data'=>$res, 'count'=>$totalCustomer, 'totalpage'=>ceil($totalCustomer/$take), 'page'=>$page, 'take'=>$take]);
    }

    public function getListCustomers(Request $request)
    {
        $user= $request->user;
        if($user->role != ROLE_ADMIN && $user->role !=ROLE_USER)
        {
            return ['error'=>'Permission denied'];
        }

        $page = 0;
        $take = 25;
        // VALIDATE
        $errors = Validator::make($request->only('query', 'page', 'take'), [
                'query' => 'sometimes|max:50',
                'page' => 'sometimes|integer|min:0',
                'take' => 'sometimes|integer|min:0',
            ]
        );
        if ($errors->fails()) {
            return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
        };
        $query=$request->query;

        $queryTotalCutomer = DB::table('customers');
        if($request->query)
        {
        // $queryTotalCutomer->whereRaw('companyname like ? OR enterprise_number like ? ', ['%'.$query.'%','%'.$query.'%']);
        }


        $totalCustomer= $queryTotalCutomer ->count();
        $res = DB::select(' 
        select a.*, IFNULL(SUM(b.total_amount),0) as callFee, 
        IFNULL(SUM(b.total_duration),0) as callDuration, 
        IFNULL(SUM(c.total_amount),0) as smsFee, 
        IFNULL(SUM(c.total_count),0) as smsCount,
        IFNULL(SUM(d.total_amount),0) as subFee  from customers a 
        left join call_fee_cycle_status b on SUBSTRING(a.enterprise_number, 2) = b.enterprise_number
        left join sms_fee_cycle_status c on SUBSTRING(a.enterprise_number, 2) = c.enterprise_number
        left join subcharge_fee_cycle_status d on SUBSTRING(a.enterprise_number, 2) = d.enterprise_number
        group by a.id 
        order by a.id desc 
        limit ?', [$take]);
//        ->select(DB::raw('IFNULL(SUM(total_amount),0) as total_amount'))
        return response()->json(['status'=>true, 'data' => $res, 'count' => $totalCustomer, 'totalpage' => ceil($totalCustomer / $take), 'page' => $page, 'take' => $take]);
    }




 /** Lấy thiết lập theo từng khách hàng  */
    public function getConfigByCustomer($id, Request $request)

    {
         $user= $request->user;
      if (!$this->checkEntity($user->id, "CONFIG_CUSTOMER") &&  !$this->checkEntity($user->id, "VIEW_BILLING_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET AccountController.getConfigByCustomer WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      // VALIDATE
        $errors = Validator::make(['enterprise_number' => $id], [
                'enterprise_number' => 'required|alpha_dash|max:250|exists:customers',
            ]
        );
        if ($errors->fails()) {
            return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
        };

        // END VALIDATE
        $customer = DB::table('customers')
            ->where('enterprise_number', $id)
            ->whereIn('blocked',[0,1])
            ->select('id', 'cus_name', 'enterprise_number','account_id', 'companyname', 'addr', 'taxcode', 'licenseno', 'dateofissue', 'issuedby', 'phone1', 'email', 'service_id', 'blocked', 'pause_state', 'blocked as status')
            ->first();
        // Check AM

        $customer->ams= DB::select("select users.id user_id, name as username, email from users join customer_ams on users.id= customer_ams.user_id where cus_id=? ",[$customer->id]);



      $tosServices= DB::select("select sl.active, sl.service_key, sc.service_name,
     sl.id service_linked_id, sl.updated_at  , sl.created_at, sl.cus_id  
     from services_apps_linked sl left join service_config sc 
    on sl.service_key= sc.product_code
    where sl.cus_id=?",[$customer->id]);


        $quantity_subcriber = DB::table("quantity_subcriber AS a")
            ->join('quantity_config AS b', 'a.quantity_config_id', '=', 'b.id')
            ->select('a.*', 'b.description')
            ->where("service_subcriber_id", $customer->id)
            ->whereIn('a.status',[0,1])
            ->get();
        $service_option = DB::table("service_option_subcriber AS a")
            ->leftJoin('service_subcriber AS b', 'a.service_subcriber_id', '=', 'b.id')
            ->select('a.*', 'b.num_agent')
            ->where("a.service_subcriber_id", $customer->id)
            ->whereIn('a.status',[0,1])
            ->get();

        $redWarning= DB::table("sbc.routing")
          ->where("caller", $customer->enterprise_number)
          ->select("auto_detect_blocking")
          ->first();

        if($redWarning)
        {
          $baodo=$redWarning->auto_detect_blocking;
        }
        else
        {
          $baodo=0;
        }
        return response()->json(['status'=>true, 'customer' => $customer,
          'fee_limit'=>$this->getFeeLimit($customer->enterprise_number),
          'hotlines' => [],
          "quantities" => $quantity_subcriber,
          "options" => $service_option,
 		  	"service_linked"=>$tosServices,
          "baodo"=>$baodo]);
    }

  public function getListHotLinesByCustomers(Request $request) {
    $user = Auth::user();
    $formData = $request->only("page", "count", "query", 'enterprise_number');
    $errors = Validator::make($formData, ['page' => 'nullable|numeric', 'count' => 'nullable|numeric|max:500', 'query' => 'nullable|number_dash|max:50']);
    if ($errors->fails()) {
      return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
    };

    $enterpriseNumber = request('enterprise_number');

    $cusID = Customers::where('enterprise_number', $enterpriseNumber)->whereIn('blocked', [0, 1])->first();

    if(!$cusID)
    {
      return ['data' => [], 'count' => 0];
    }

    $pagenum = request('page', 1);
    $count = request('count', 50);
    $query = request('query', null);

    $start = ($pagenum - 1) * $count;
    $limit = $count;

    $sql = "select hlc.*, operator_telco.DESCRIPTION as operator_name,  cg.status as group_call_status, cg.enterprise as caller_group_master from hot_line_config hlc 
            force index (status,cus_id)
             left join operator_telco on  hlc.operator_telco_id=operator_telco.id
            left join sbc.caller_group cg 
            on hlc.hotline_number = cg.caller           
            and  hlc.cus_id= cg.cus_id
            where hlc.cus_id= ?
            and hlc.status in (0,1)    ";

    $sqlCount = "select count(*) total from hot_line_config hlc
      force index (status,cus_id)
      where hlc.cus_id= ?     and hlc.status in (0,1)  ";

    $param = [$cusID->id];

    if ($query) {
      $sql .= " AND hlc.hotline_number like ? ";
      $sqlCount .= " AND hlc.hotline_number like ? ";
      array_push($param, "%$query%");
    }

    $total = DB::select($sqlCount, $param)[0]->total;

    $sql .= " LIMIT ?, ?";
    array_push($param, $start, $limit);

    $hotlines = DB::select($sql, $param);

    return ['data' => $hotlines, 'count' => $total];
  }



    public function rules()
    {
        return [
            'title' => 'required',
            'body' => 'required',
        ];
    }




    public function postServiceCustomerOption(Request $request)
    {

      $startTime=round(microtime(true) * 1000);

        $user= $request->user;
      if (!$this->checkEntity($user->id, "CONFIG_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET AccountController.postServiceCustomerHotline WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }



      $arrConfigPrice= $request->validate([


            "enterprise_number"=>'required|alpha_dash|exists:customers',
            "begin_charge_date"=>'required|date',
            "status"=>'required|in:0,1',
            "extension_count"=>'required|numeric',
            "call_record_storage_count"=>'required|numeric',
            "data_storage_count"=>'required|numeric',
            "api_count"=>'required|numeric',
            "api_rpm_count"=>'required|numeric',
            "softphone_3c_count"=>'required|numeric',
            "num_agent"=>'required|numeric',


        ]);
        $arrConfigPrice["begin_charge_date"] = date('Y-m-d', strtotime($arrConfigPrice["begin_charge_date"]));

        $cus_id=DB::table('customers')
            ->where('enterprise_number', $request->enterprise_number)
            ->first()->id;

        unset($arrConfigPrice['enterprise_number']);
        $arrConfigPrice['service_subcriber_id']=$cus_id;

        $resID = $request->validate([
            'id' => 'nullable|numeric'
        ]);
        $id = $request->id;




        // return response()->json($arrConfigPrice);
        if (!$id) {
            // Update number of agent
            $res= DB::table('service_subcriber')
                ->where('enterprise_number', $request->enterprise_number)
                ->update(['num_agent'=>$request->num_agent,'updated_at'=>date("Y-m-d H:i:s")]);
            $this->Activity(['num_agent'=>$request->num_agent,'updated_at'=>date("Y-m-d H:i:s")], "service_subcriber", 0, $request->cus_id, "Update");
            unset($arrConfigPrice['num_agent']);
            $res = DB::table('service_option_subcriber')
                ->insertGetId($arrConfigPrice);
            $this->Activity($arrConfigPrice, "service_option_subcriber", $res, 0, "Create");
        } else {

            $res= DB::table('service_subcriber')
                ->where('enterprise_number', $request->enterprise_number)
                ->update(['num_agent'=>$request->num_agent,'updated_at'=>date("Y-m-d H:i:s")]);
            $this->Activity(['num_agent'=>$request->num_agent,'updated_at'=>date("Y-m-d H:i:s")], "service_subcriber", 0, $request->cus_id, "Update");
            unset($arrConfigPrice['num_agent']);
            DB::table('service_option_subcriber')
                ->where('id', $id)
                ->update($arrConfigPrice);
            $res = $id;
            $this->Activity($arrConfigPrice, "service_option_subcriber", $id, 0, "Update");
        }

      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|"
        .json_encode($request->all())."|ADD_EDIT_CUSTOMER_OPTION|".$logDuration."|ADD_EDIT_CUSTOMER_OPTION_SUCCESS");


      return response()->json(array('status' => true, 'code' => 200, 'id' => $res, 'data' => $arrConfigPrice));

    }

    public function postServiceCustomerQuantity(Request $request)
    {
      $BACKUPSTATE=$request->single_mode==1?false:config("server.backup_site");
      $API_STATE= $request->api_source?"API|":"WEB|";

      $startTime= round(microtime(true) * 1000);
        $user= $request->user;
      if (!$this->checkEntity($user->id, "CONFIG_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET AccountController.postServiceCustomerQuantity WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }



      $validate = $request->validate([
            'quantity_config_id' => 'required|exists:quantity_config,id',
            'enterprise_number'=>'required|exists:customers,enterprise_number',
            'resub'=>'required|in:0,1',
            'begin_use_date'=>'required|date',
            'status'=>'required|in:0,1,2'
        ]);
        $id = $request->input('id');


        $isRunOnBackup= false;

      $enterprise= $request->enterprise_number;
      $customer=Customers::where('enterprise_number',$enterprise)->whereIn('blocked',[0,1])->first();

      if(!$customer)
      {
        return $this->ApiReturn([], false, 'Not found active enterprise number', 422);

      }

      if($BACKUPSTATE)
      {
        $customerBackup= CustomersBackup::where('enterprise_number',$enterprise)->whereIn('blocked',[0,1])->first();

        $isRunOnBackup=$customer->server_profile==config("server.server_profile")?false:true;
        if(!$customerBackup)
        {
          return $this->ApiReturn([], false, 'Not found active enterprise number on backup', 422);

        }

        $quantitySubriberCheckBackup = DB::connection("db2")->select("select q.id from service_config s join quantity_config q on s.id= q.service_config_id where s.id =? and q.status=0", [$customerBackup->service_id]);

        if(count($quantitySubriberCheckBackup)!=1)
        {
          return $this->ApiReturn([], false, 'Not found quantity config on package on backup server', 422);
        }

        $resubOn= $isRunOnBackup?$request->input('resub'):0;
        $isStatus=$isRunOnBackup?$request->input('status'):1;

        $arrConfigPriceBackup = [
          'quantity_config_id' => $quantitySubriberCheckBackup[0]->id,
          'status' => $isStatus,
          'begin_use_date' => $request->input('begin_use_date'),
          'resub' => $resubOn,
          'created_at' => date("Y-m-d H:i:s"),
          'updated_at' => date("Y-m-d H:i:s"),
          'service_subcriber_id'=>$customerBackup->id];




        if($request->begin_use_date)
        {

          $arrConfigPriceBackup['begin_use_date']=  date('Y-m-d', strtotime($request->begin_use_date));
        }


        // Check if Exists on backup

        $resQuantityBackup=
          QuantitySubcriberBackup::where("service_subcriber_id", $customerBackup->id)
            ->where('quantity_config_id',$quantitySubriberCheckBackup[0]->id)
            ->first();

        if($resQuantityBackup)
        {

          $resQuantityBackup->status= $isStatus;
          $resQuantityBackup->resub= $resubOn;
          $resQuantityBackup->begin_use_date=   $arrConfigPriceBackup['begin_use_date'];
          $resQuantityBackup->service_subcriber_id= $customerBackup->id;
          $resQuantityBackup->save();


        }
        else
        {
          Log::info("Quantity Subriber create  on backup");

          QuantitySubcriberBackup::insert($arrConfigPriceBackup);
        }


      }

        $arrConfigPrice = [
            'quantity_config_id' => $request->input('quantity_config_id'),
          'status' => $isRunOnBackup?1:$request->input('status'),
          'begin_use_date' => $request->input('begin_use_date'),
          'resub' => $isRunOnBackup?0:$request->input('resub'),
          'created_at' => date("Y-m-d H:i:s"),
          'updated_at' => date("Y-m-d H:i:s"),
          'service_subcriber_id'=>$customer->id
        ];


        if($request->begin_use_date)
        {
            $arrConfigPrice['begin_use_date']=  date('Y-m-d', strtotime($request->begin_use_date));

        }

        if (!$id) {

            $res = DB::table('quantity_subcriber')
                ->insertGetId($arrConfigPrice);
            $this->SetActivity($arrConfigPrice, "quantity_subcriber",$res,0 , config("sbc.action.set_quantity_subscriber"), "Thêm mới gói sản lượng", $enterprise, null);

        } else {
            unset($arrConfigPrice['created_at']);
            DB::table('quantity_subcriber')
                ->where('id', $id)
                ->update($arrConfigPrice);
            $res = $id;
           $this->SetActivity($arrConfigPrice, "quantity_subcriber",$res,0 , config("sbc.action.set_quantity_subscriber"), "Cập nhật  gói sản lượng", $enterprise, null);
        }

      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|"
        .json_encode($request->all())."|ADD_EDIT_CUSTOMER_QUANTITY|".$logDuration."|ADD_EDIT_CUSTOMER_QUANTITY_SUCCESS");




      return response()->json(array('status' => true, 'code' => 200, 'id' => $res, 'data' => $arrConfigPrice));

    }




  public function saveFeeLimit(Request $request) {

      // DONE BACKUP
    $startTime = round(microtime(true) * 1000);
    $apiRequest= $request->api_source;

    $user = $request->user;
    if (!$this->checkEntity($user->id, "CONFIG_FEE_LIMIT")) {
      Log::info($user->email . '  TRY TO GET AccountController.saveFeeLimit WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validData = $request->only('limit_amount', 'enterprise_number');

    $validator = Validator::make($validData, ['enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
      'limit_amount' => 'required|integer|max:9999999999']);
    if ($validator->fails()) {
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validData) . "|ADD_EDIT_FEE_LIMIT|" . $logDuration . "|SAVE_FAIL Invalid input data ");

      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }

    $updated_at = date('Y-m-d H:i:s');


    $enterprise= $request->enterprise_number;
    $customer=Customers::where('enterprise_number',$enterprise)->whereIn('blocked',[0,1])->first();

    if(!$customer)
    {
      return $this->ApiReturn([], false, 'Not found active enterprise number', 422);

    }


    $litmitAmountText= $request->limit_amount>-1?$request->limit_amount:"xóa hạn mức";


    $res = DB::table('charge_fee_limit')->where('enterprise_number', $request->enterprise_number);
    if ($res->exists()) {
      if ($request->limit_amount > -1) {
        $res->update(['limit_amount' => $request->limit_amount, 'updated_at' => $updated_at,'over_quota_status'=>1]);
        $this->SetActivity($validData, "charge_fee_limit", 0, 0, config("sbc.action.set_fee_limit"),"Thay đổi hạn mức  thành ".$litmitAmountText, $enterprise, null);

      } else {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_FEE_LIMIT|" . $logDuration . "|DELETE_FEE_LIMIT_SUCCESS");

        $res->delete();

        $this->SetActivity($validData, "charge_fee_limit", 0, 0, config("sbc.action.set_fee_limit"),"Xóa hạn mức hiện tại", $enterprise, null);

      }
    } else {

      if ($request->limit_amount > -1)
      {
        $this->SetActivity($validData, "charge_fee_limit", 0, 0, config("sbc.action.set_fee_limit"),"Thiết lập hạn mức thành ".$litmitAmountText, $enterprise, null);
        $res->insertGetId($validData);
      }

    }



    $logDuration = round(microtime(true) * 1000) - $startTime;
    Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_EDIT_FEE_LIMIT|" . $logDuration . "|ADD_EDIT_FEE_LIMIT_SUCCESS");

    return $this->ApiReturn([], true, null, 200);
  }

    // FEE LIMIT LOG


    public function saveAddFeeLimit(Request $request)
    {
        $startTime = round(microtime(true) * 1000);
        $apiRequest= $request->api_source;

        $user = $request->user;
        if (!$this->checkEntity($user->id, "CONFIG_FEE_LIMIT")) {
            Log::info($user->email . '  TRY TO GET AccountController.saveAddFeeLimit WITHOUT PERMISSION');
            return response()->json(['status' => false, 'message' => "Permission denied"], 403);
        }


        $validData = $request->only('limit_amount', 'enterprise_number','amount','reason' );

        $validator = Validator::make($validData, [
            'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
            'amount' => 'required|integer|max:9999999999',
            'reason' => 'nullable|max:250'
        ]);
        if ($validator->fails()) {
            $logDuration = round(microtime(true) * 1000) - $startTime;
            Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validData) . "|ADD_EDIT_FEE_LIMIT|" . $logDuration . "|SAVE_FAIL Invalid input data ");

            return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }



        $enterprise= $request->enterprise_number;
        $customer=Customers::where('enterprise_number',$enterprise)->whereIn('blocked',[0,1])->first();

        if(!$customer)
        {
            return $this->ApiReturn([], false, 'Not found active enterprise number', 422);
        }
        // Get Curent Fee Limti


        DB::beginTransaction();
        try{
            $resCurentLimit=ChargeFeeLimit::where('enterprise_number', $enterprise)
                ->first();

            if(!$resCurentLimit)
            {

                return $this->ApiReturn([], false, 'Not found previous limit of '.$enterprise.'', 422);

            }
            $newLimitAmount = intval($request->amount)+ intval($resCurentLimit->limit_amount);

            $newAddLimitLog= new FeeLimitLog();
            $newAddLimitLog->enterprise_number= $enterprise;
            $newAddLimitLog->amount= request('amount');
            $newAddLimitLog->new_limit_amount= $newLimitAmount;
            $newAddLimitLog->reason= request('reason');
            $newAddLimitLog->cus_id= $customer->id;
            $newAddLimitLog->user_id= $user->id;
            $newAddLimitLog->save();



            $resCurentLimit->limit_amount= $newLimitAmount;
            $resCurentLimit->save();
            DB::commit();
        }
        catch (\Exception $exception)
        {

            DB::rollback();

            Log::info("ERROR ADD FEE LIMIT");
            Log::info($exception->getTraceAsString());


            return ['status'=>false,'message'=>'Internal server error'];
        }
        return ['status'=>true];

    }


    public function getFeeLimitLogs(Request $request) {
        $startTime = round(microtime(true) * 1000);
        $apiRequest = $request->api_source;

        $user = $request->user;
        if (!$this->checkEntity($user->id, "CONFIG_FEE_LIMIT")) {
            Log::info($user->email . '  TRY TO GET AccountController.saveAddFeeLimit WITHOUT PERMISSION');
            return response()->json(['status' => false, 'message' => "Permission denied"], 403);
        }

        $validData = $request->only('limit_amount', 'enterprise_number');

        $validator = Validator::make($validData, [
            'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
        ]);
        if ($validator->fails()) {
            $logDuration = round(microtime(true) * 1000) - $startTime;
            Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validData) . "|ADD_EDIT_FEE_LIMIT|" . $logDuration . "|SAVE_FAIL Invalid input data ");

            return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }

        $enterprise = $request->enterprise_number;
        $customer = Customers::where('enterprise_number', $enterprise)->whereIn('blocked', [0, 1])->first();

        if (!$customer) {
            return $this->ApiReturn([], false, 'Not found active enterprise number', 422);
        }
        // Get Curent Fee Limti

        $lsFeeLimitLog= FeeLimitLog::where('enterprise_number',$enterprise)->where('cus_id',$customer->id)->orderBy('updated_at', 'DESC')->get();

        return $this->ApiReturn($lsFeeLimitLog, true, null, 200);

    }

    private function getFeeLimit($enterprise)
    {

        $res =DB::table('charge_fee_limit')
            ->where('enterprise_number',$enterprise)
            ->select('updated_at','limit_amount')
            ->first();

        return $res;
    }


    public function saveRedWarning(Request $request)
    {
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;
      if (!$this->checkEntity($user->id, "CONFIG_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET AccountController.saveRedWarning WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


      $validData = $request->only('action', 'enterprise_number');

      $validator = Validator::make($validData,
        ['enterprise_number' => 'required|exists:customers,enterprise_number',
          'action' => 'required|in:0,1']);
      if ($validator->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validData) . "|SAVE_BAO_DO|" . $logDuration . "|SAVE_BAO_DO_FAIL Invalid input data ");

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }


      $prepareMethod=['auto_detect_blocking'=>$request->action];

      $cus= DB::table("customers")->where('enterprise_number',$request->enterprise_number)
        ->where('blocked',0)
        ->first();

      Log::info(APP_API."|SAVE_RED_WARNING|".json_encode($cus)."|INPUT|".json_encode($validData));


      $res= DB::table("sbc.routing" )
        ->where('i_customer', $cus->id)
        ->update($prepareMethod);
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validData) . "|SAVE_BAO_DO|" . $logDuration . "|SAVE_SUCCESS");

      return $this->ApiReturn([], true, $res, 200);

    }


}
