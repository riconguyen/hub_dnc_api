<?php
namespace App\Http\Controllers;


use App\ChargeFeeLimit;
use App\ChargeLog;
use App\Customers;
use App\CustomersBackup;
use App\FeeLimitLog;
use App\Hotlines;
use App\HotlinesBackup;
use App\HotlineStatusLog;
use App\HotlineStatusLogBackup;
use App\QuantitySubcriber;
use App\QuantitySubcriberBackup;
use App\SBCCallGroup;
use App\SBCRouting;
use App\ServiceConfig;
use App\ServiceConfigBackup;
use App\TosServices;
use App\TosServicesBackup;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Validator;

class V1CustomerController extends Controller
{
    //


    private function synCustomerToServiceSub($data, $BACKUP_STATE)
    {

        if ($data['split_contract']) {

            $begin_charge_date = date_create(date('Y-m-01 H:i:s'));
            date_modify($begin_charge_date, "+1 month");
            Log::info("SPLIT CONTRACT FROM: " . $data['split_contract']);
        } else {
            $begin_charge_date = date_create(date('Y-m-d H:i:s'));
            date_modify($begin_charge_date, "+" . config("sbc.delay_sub_charge_in_minutes") . " minutes");
        }

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
        if ($resExist) {
            // Check extist data
            unset($postData["num_agent"]);
            unset($postData["begin_charge_date"]);


            DB::table('service_subcriber')
                ->where('id', $data["cus_id"])
                ->update($postData);

            // UPDATE OTHER TABLE HERE
            DB::table('hot_line_config')
                ->where('id', $data["cus_id"])
                ->update(['enterprise_number' => $data['enterprise_number'], 'updated_at' => $postData['updated_at']]);


        } else {
            $subid = DB::table('service_subcriber')
                ->insert($postData);
            //$this->Activity($postData, "service_subcriber", $data["cus_id"], 0, "Created");
        }
        return true;


    }

    public function getCustomers(Request $request)
    {
      $startTime=round(microtime(true) * 1000);
        $user= $request->user;

      if (!$this->checkEntity($user->id, "VIEW_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET V1CustomerController.getCustomers WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 400);
      }
      $page = 0;
        $take = 200;
        // Valid data
        $validData = $request->only('query', 'page');
        $validator = Validator::make($validData, [
            'query' => 'nullable|unicode_valid|max:50',
            'page' => 'sometimes|numeric|max:9999999999'
        ]);
        // Trả về lỗi nếu sai
        if ($validator->fails()) {

          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|GET_CUSTOMERS|".$logDuration."|Invalid parameter");

            return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }
// Phân trang
         if ($request->page  && $request->page > 0) {
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
                ->whereRaw(' enterprise_number like ? ', ["%$query%"])
                ->count();
            $res = DB::table('customers AS a')
                ->leftJoin('service_config As b', 'a.service_id', '=', 'b.id')
                ->whereRaw('a.enterprise_number like ?', ["%$query%"])
                ->select('cus_name', 'enterprise_number', 'companyname','a.server_profile',
                    'addr', 'phone1', 'email', 'product_code',
                    'blocked as status', 'ip_auth', 'ip_proxy', 'destination', 'b.service_name', 'b.product_code', 'a.created_at', 'a.updated_at')
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
                ->select('cus_name', 'enterprise_number', 'companyname','a.server_profile',
                    'addr', 'phone1', 'email', 'product_code',
                    'blocked as status', 'ip_auth', 'ip_proxy', 'destination', 'b.service_name', 'b.product_code', 'a.created_at', 'a.updated_at')
                ->groupBy("a.id")
                ->orderBy("a.id", 'DESC')
                ->take($take)
                ->skip($skip)
                ->get();
        }

      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|GET_CUSTOMERS|".$logDuration."|SUCCESS");

      return response()->json(['status' => true, 'data' => $res, 'count' => $totalCustomer,  'totalpage' => ceil($totalCustomer / $take)], 200);
    }

  /**
   * @param Request            $request
   * @param ActivityController $activityController
   * @return \Illuminate\Http\JsonResponse|null
   */

  public function addCustomer(Request $request) {

    $BACKUPSTATE=$request->single_mode==1?false:config("server.backup_site");
    $API_STATE= $request->api_source?"API|":"WEB|";
    $startTime = round(microtime(true) * 1000);
    $useTos = false;
    $user = $request->user;
    if (!$this->checkEntity($user->id, "ADD_CUSTOMER")) {
      Log::info($user->email . '  TRY TO GET V1CustomerController.addCustomer WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    // Lấy dữ liệu
    $validData = $request->only('cus_name', 'enterprise_number', 'companyname', 'addr', 'phone1', 'email', 'product_code', 'status', 'ip_auth', 'ip_proxy', 'destination', 'hotline_numbers', 'split_contract', 'profile_id_backup', 'fee_limit', 'use_tos', 'tos_product_code', 'ip_auth_backup', 'ip_proxy_backup'

    );



    $tosProductCode = $request->tos_product_code;
    $tosUse = $request->use_tos;


    // Kiểm tra
    $validator = Validator::make($request->all(), ['cus_name' => 'required|max:250',
      'enterprise_number' => 'required|alpha_dash|min:5|max:250|unique:customers,enterprise_number',
      'companyname' => 'required|max:250',
      'addr' => 'required|max:250',
      'phone1' => 'required|number_dash|max:250',
      'email' => 'required|max:250',
      'product_code' => 'required|alpha_dash|max:50|exists:service_config,product_code',
      'status' => 'required|in:0,1',
      'auto_detect_blocking' => 'nullable|in:0,1',
      'ip_auth' => 'required|ipv4|max:50',
      'ip_proxy' => 'nullable|ipv4|max:50',
      'ip_auth_backup' => 'nullable|ipv4|max:50',
      'ip_proxy_backup' => 'nullable|ipv4|max:50',
      'destination' => 'required|ipV4Port|max:50',
      'telco_destination' => 'nullable|ipV4Port|max:50',
      'operator_telco_id' => 'required|max:10|exists:operator_telco,id',
      'hotline_numbers' => 'required|number_dash|max:1500',
      'split_contract' => 'nullable|max:25|exists:customers,enterprise_number',
      'fee_limit' => 'nullable|integer|max:9999999999',
      'profile_id_backup' => "nullable|in:2,3,4",
      "use_tos"=>"nullable|in:0,1",
      "tos_product_code"=>"nullable|alpha_dash|max:50",
      'services' => 'nullable|array']);

    // Trả lỗi kiểm tra đầu vào
    if ($validator->fails()) {
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validData) . "|ADD_CUSTOMER|" . $logDuration . "|ERROR INPUT DATA");

      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }

    
    // Kiểm tra tồn tại dịch vụ
    $resServiceId = DB::table('service_config')->where('product_code', $request->product_code)->where('status', 0)->select('id','ocs_charge')->first();


    if (!$resServiceId) {
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validData) . "|ADD_CUSTOMER|" . $logDuration . "|ERROR SERVICE ID");

      return $this->ApiReturn(['product_code' => ['Product code Not available or is temporary disabled']], false, 'The given data was invalid', 422);
    }
    $use_brand_name=false;
    if ($resServiceId) {
        $use_brand_name = ServiceConfig::where("id", $resServiceId->id)->where("product_code", "like", '%VB%')->exists();
    }



    if ($request->hotline_numbers) {
      if (strpos($request->hotline_numbers, ',') !== false) {
        $listHotline = explode(',', $request->hotline_numbers);
      }
      else
      {
        $listHotline = [$request->hotline_numbers];
      }
        // Multiple

        $listHotline= array_unique($listHotline);
      
        $inValidHotline = DB::table('hot_line_config')->whereIn('hotline_number', $listHotline)->whereIn('status', [0, 1])->count();
        if ($inValidHotline > 0) {
          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($listHotline) . "|ADD_CUSTOMER|" . $logDuration . "|HOTLINES IN USED");

          return $this->ApiReturn(['hotline_numbers' => ["Hotlines number in uses"]], false, 'The given data was invalid', 422);
        }

      // Processing DB For Hotline
    }
    $email_login_portal = $request->enterprise_number;
    if (DB::table("users")->where("email", $email_login_portal)->exists()) {
      // Kiem tra xem co chua

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", $startTime) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($email_login_portal) . "|ADD_CUSTOMER|" . $logDuration . "|EMAIL BILLING EXISTS");
      return $this->ApiReturn(['email' => ["User login billing already exists"]], false, 'The given data was invalid', 422);
    }




    $enterprise = $request->enterprise_number;
     // Gán ID
    // $validData['service_id'] = $resServiceId->id;
    /**
     * THÊM KHÁCH HÀNG
     */

    $ip_auth = $request->ip_auth;
    $ip_proxy = $request->ip_proxy;
    $destination = $request->destination;
    $telco_destination = request("telco_destination", null);
      $operator_telco_id = request("operator_telco_id", null);
    $ip_auth_backup = $request->ip_auth_backup ? $request->ip_auth_backup : null;
    $ip_proxy_backup = $request->ip_proxy_backup ? $request->ip_proxy_backup : null;

    $auto_detect_blocking=request('auto_detect_blocking',1);


    $newCustomer = $request->only('cus_name', 'enterprise_number', 'companyname', 'addr', 'phone1', 'email', 'ip_auth', 'ip_proxy', 'destination', 'ip_auth_backup', 'ip_proxy_backup', 'blocked');
    $newCustomer['service_id'] = $resServiceId->id;
    $newCustomer['blocked'] = $request->status;
    $newCustomer['auto_detect_blocking'] = $auto_detect_blocking;
    $newCustomer['pause_state'] = "10";
    $newCustomer['server_profile']=config("server.server_profile");
    $newCustomer['telco_destination']=$telco_destination;
    $newCustomer['operator_telco_id']=$operator_telco_id;


    $vendorData = DB::table('sbc.vendors')->where('i_vendor', $request->vendor_id ? $request->vendor_id : 1)->first();




    // Check Services

    DB::beginTransaction();


    try {

      $billingAccount = [
        'email' => $email_login_portal,
        'role' => ROLE_BILLING,
        'name' => $request->companyname];

      $billingAccount['password'] = Hash::make($enterprise);
        $user = User::create($billingAccount);
        $newCustomer['account_id']=$user->id;
        $newCustomer['cus_id'] = Customers::insertGetId($newCustomer);

      // Backup
      $this->SetActivity($request->all(), 'customers', $newCustomer['cus_id'], 0, config("sbc.action.create_customer"), "Tạo mới khách hàng " . $request->companyname, $enterprise, null);

      $CDR_ADD_PRODUCTCODE=$enterprise."|3|".date("YmdHis")."|".$request->product_code;
      $this->CDRActivity(config("server.server_profile"), $CDR_ADD_PRODUCTCODE,$enterprise,$API_STATE."ADD_CUSTOMER");
      $newCustomer['split_contract'] = $request->split_contract;
      $isBackup= false;

      // Thêm gói sản lượng

      $begin_charge_date = date_create(date('Y-m-d H:i:s'));
      date_modify($begin_charge_date, "+".config("sbc.delay_quantity_charge_in_minutes")." minutes");


      $quantitySubcriberCheck=
        DB::select("select q.id from service_config s join quantity_config q on s.id= q.service_config_id where s.product_code =? and q.status=0",[$request->product_code]);
        if(count($quantitySubcriberCheck)==1)
        {

          $newQuantity=new QuantitySubcriber();
          $newQuantity->service_subcriber_id= $newCustomer['cus_id'];
          $newQuantity->status= 0;
          $newQuantity->resub= 1;
          $newQuantity->begin_use_date= $begin_charge_date;
          $newQuantity->quantity_config_id= $quantitySubcriberCheck[0]->id;
          $newQuantity->save();
          Log::info("AUTO ADD QUANTITY SUBSCRIBER PACKAGE");
          Log::info(json_encode($newQuantity));

        }



      /**
       * THÊM HOTLINE
       */
      $sip = (object)[];
        $listHotline= array_unique($listHotline);
        if (count($listHotline) > 0) {
        foreach ($listHotline as $key => $line) {
          $data = array('cus_id' => $newCustomer['cus_id'],
            'enterprise_number' => $request->enterprise_number,
            'hotline_number' => $line, 'status' => $request->status,
            'use_brand_name'=>$use_brand_name,
            'ocs_charge'=>$resServiceId->ocs_charge,
            'operator_telco_id'=>$operator_telco_id,
              'auto_detect_blocking'=>$auto_detect_blocking

          );


          // $this->hotline->addHotline($data);

          $hotlineId=   Hotlines::insertGetId($data);

          // CDR CREATE

          $CDR_TEXT= $enterprise."|".config("sbc.CDR.CHANGE")."|".date("YmdHis")."|".$line;
          $this->CDRActivity(config("server.server_profile"),$CDR_TEXT,$enterprise,$API_STATE."ADD_HOTLINE");

          $this->SetActivity($data,'hot_line_config', $hotlineId, 0,config("sbc.action.add_hotline"), "Tạo mới hotline ".$line, $request->enterprise_number, $line);

          $sip->hotline = $line;
          $sip->cus_id=$newCustomer['cus_id'];
          $sip->hotline_id=$hotlineId;
          $sip->enterprise_number = $request->input('enterprise_number');

          $sip->description = $request->input('enterprise_number');

          $sip->profile_id_backup = $request->profile_id_backup ? $request->profile_id_backup : config('sbc.profile_id_backup');

          $sip->destination = $destination;
          $sip->telco_destination = $telco_destination;
          $sip->ip_proxy = $ip_proxy;
          $sip->ip_auth = $ip_auth;
          $sip->ip_proxy_backup = $ip_proxy_backup;
          $sip->ip_auth_backup = $ip_auth_backup;
          $sip->vendor = $vendorData;
          $sip->isRunOnBackup = false;
          $sip->status = $request->status;
          $sip->auto_detect_blocking =$auto_detect_blocking;

          $sipOk = $this->addSipRouting($sip,false);

        }
      }



      $this->synCustomerToServiceSub($newCustomer,  false);

      /** Thêm mới bản ghi Fee limit */
      if ($request->fee_limit) {
        $dataFeelimit = ["enterprise_number" => $request->enterprise_number, "limit_amount" => $request->fee_limit];

        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($dataFeelimit) . "|ADD_FEE_LIMIT|SUCCESS");
        $feeLogId= DB::table("charge_fee_limit")->insertGetId($dataFeelimit);
        $this->SetActivity($dataFeelimit,'charge_fee_limit', $feeLogId, 0,config("sbc.action.set_fee_limit"), "Thiết lập hạn mức ".$dataFeelimit['limit_amount'], $request->enterprise_number, null);


      }

      /** Thêm bản ghi tos  */
      if ($tosProductCode) {


        if (isset($listUseTosCode) && count($listUseTosCode) > 0) {
          foreach ($listUseTosCode as $key => $value) {
            $postData = ['cus_id' =>  $newCustomer['cus_id'], 'service_key' => $value, 'enterprise_number' => $enterprise, 'active' => $listUseTos[$key]];

            if (TosServices::where('enterprise_number', $enterprise)->where('service_key', $value)->exists()) {
               TosServices::where('enterprise_number', $enterprise)->where('service_key', $value)->update($postData);
            } else {
              TosServices::create($postData);
            }
          }
        } else {
          $postData = ['cus_id' => $newCustomer['cus_id'], 'service_key' => $tosProductCode, 'enterprise_number' => $enterprise, 'active' => $tosUse];
          if (TosServices::where('enterprise_number', $enterprise)->where('service_key', $tosProductCode)->exists()) {
             TosServices::where('enterprise_number', $enterprise)->where('service_key', $tosProductCode)->update($postData);
          } else {
           TosServices::create($postData);
          }
        }

      }


      DB::commit();

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validData) . "|ADD_CUSTOMER|" . $logDuration . "|SUCCESS");

      return $this->ApiReturn(null, true, null, 200);
    } catch (Exception $exception) {
      DB::rollBack();

      return $this->ApiReturn($exception, true, null, 433);
    }
  }

    public function editCustomer(Request $request)
    {
      $BACKUPSTATE=$request->single_mode==1?false:config("server.backup_site");
      //DONE BACKUP
      $startTime= round(microtime(true) * 1000);
        $user= $request->user;

          if (!$this->checkEntity($user->id, "UPDATE_CUSTOMER")) {
            Log::info($user->email . '  TRY TO GET V1CustomerController.editCustomer WITHOUT PERMISSION');
            return response()->json(['status' => false, 'message' => "Permission prohibit"], 400);
          }

        $validData = $request->only('cus_name', 'enterprise_number', 'companyname', 'addr', 'phone1', 'email', 'ip_auth','ip_proxy','destination', 'telco_destination',  'ip_auth_backup',
          'ip_proxy_backup','operator_telco_id');
        $validator = Validator::make($validData, [
            'cus_name' => 'nullable|max:250',
            'enterprise_number' => 'required|alpha_dash|max:25|min:5|exists:customers,enterprise_number',
            'companyname' => 'nullable|max:250',
            'addr' => 'nullable|max:250',
            'phone1' => 'nullable|number_dash|max:250',
            'email' => 'nullable|max:250',
            'auto_detect_blocking' => 'nullable|in:0,1',
            'ip_auth' => 'nullable|ipv4|max:100',
            'ip_proxy' => 'nullable|ipv4|max:100',
          'ip_auth_backup' => 'nullable|ipv4|max:100',
          'ip_proxy_backup' => 'nullable|ipv4|max:100',
            'destination' => 'nullable|ipV4Port|max:100',
            'telco_destination' => 'nullable|ipV4Port|max:50',
            'operator_telco_id' => 'required|max:50|exists:operator_telco,id',
        ]);
        if ($validator->fails()) {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|UPDATE_CUSTOMER|".$logDuration."|UPDATE_FAIL INVALID INPUT DATA");


          return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }

        $enterprise= $request->enterprise_number;
        $customer=Customers::where('enterprise_number',$enterprise)->whereIn('blocked',[0,1])->first();


        if(!$customer)
        {
          return $this->ApiReturn([], false, 'Not found active enterprise number', 422);

        }
        $customer->cus_name= request('cus_name', null);
        $customer->companyname= request('companyname', null);
        $customer->addr= request('addr', null);
        $customer->phone1= request('phone1', null);
        $customer->email= request('email', null);
        $customer->ip_auth= request('ip_auth', null);
        $customer->ip_proxy= request('ip_proxy', null);
        $customer->destination= request('destination', null);
        $customer->telco_destination= request('telco_destination', null);
        $customer->operator_telco_id= request('operator_telco_id');
        $customer->auto_detect_blocking= request('auto_detect_blocking',1);
        $customer->ip_auth_backup= request('ip_auth_backup', null);
        $customer->ip_proxy_backup= request('ip_proxy_backup', null);

        $customer->save();




        $this->SetActivity($validData, "customers", $customer->id, 0, config("sbc.action.update_customer"),"Cập nhật thông tin khách hàng", $enterprise, null);

      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|UPDATE_CUSTOMER|".$logDuration."|UPDATE_SUCCESS");


      return $this->ApiReturn(null, true, null, 200);
    }

    public function changeCustomerProductCode(Request $request)
    {
      $BACKUPSTATE=$request->single_mode==1?false:config("server.backup_site");
      $API_STATE= $request->api_source?"API|":"WEB|";
        $startTime= round(microtime(true) * 1000);
      $user= $request->user;

      if (!$this->checkEntity($user->id, "CHANGE_PRODUCT_CODE")) {
        Log::info($user->email . '  TRY TO GET V1CustomerController.changeCustomerProductCode WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }



      $validData = $request->only('enterprise_number', 'new_product_code');
        $validator = Validator::make($validData, [
            'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
            'new_product_code' => 'required|alpha_dash|max:250|exists:service_config,product_code'
        ]);
        if ($validator->fails()) {


          /** @var LOG  $logDuration */
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
            $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_FAIL Invalid input data");
          /** @var LOG  $logDuration */


          return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }
        $resServiceID = DB::table('service_config')
            ->where('product_code', $request->new_product_code)
            ->where('status', 0)
            ->select('id','ocs_charge')
            ->first();

        if (!$resServiceID) {

          /** @var LOG  $logDuration */
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
            $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_FAIL Invalid Product code");
          /** @var LOG  $logDuration */

          return $this->ApiReturn(['new_product_code' => 'Product code is not avaiable'], false, 'The given data was invalid', 422);
        }

      $use_brand_name = false;
      if ($resServiceID) {
        $use_brand_name = ServiceConfig::where("id", $resServiceID->id)->where("product_code", "like", '%VB%')->exists();
      }

        if($BACKUPSTATE)
        {
          $resServiceIDBackup = DB::connection("db2")->table('service_config')
            ->where('product_code', $request->new_product_code)
            ->where('status', 0)
            ->select('id','ocs_charge')
            ->first();

          if (!$resServiceIDBackup) {

            /** @var LOG  $logDuration */
            $logDuration= round(microtime(true) * 1000)-$startTime;
            Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
              $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_FAIL Invalid Product code on backup");
            /** @var LOG  $logDuration */

            return $this->ApiReturn(['new_product_code' => 'Product code is not avaiable on Backup'], false, 'The given data was invalid', 422);
          }
        }

        $enterprise=$request->enterprise_number;

        $customer= Customers::where('enterprise_number', $enterprise)->whereIn('blocked',[0,1])->first();

        if(!$customer)
        {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
            $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_FAIL Invalid enterprise number");
          /** @var LOG  $logDuration */

          return $this->ApiReturn(['enterprise_number' => 'Enterprise number not active'], false, 'The given data was invalid', 422);
        }

      // Gói cước đang dùng có cùng loại OCS Charge type không

      $currentService = ServiceConfig::where('id', $customer->service_id)->where("ocs_charge", $resServiceID->ocs_charge)->first();

      if (!$currentService) {
        Log::info("$enterprise Change product code  to  $request->new_product_code The Product Code incompatible OCS Charge type with previous package on Backup server ");

//        return $this->ApiReturn(['new_product_code' => 'The Product Code incompatible OCS Charge type with previous package'], false, 'The given data was invalid', 422);
      }




      if($BACKUPSTATE)

        { $customerBackup= CustomersBackup::where('enterprise_number', $enterprise)->whereIn('blocked',[0,1])->first();

          if(!$customerBackup)
          {
            $logDuration= round(microtime(true) * 1000)-$startTime;
            Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
              $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_FAIL Invalid enterprise number");
            /** @var LOG  $logDuration */

            return $this->ApiReturn(['enterprise_number' => 'Enterprise number not found on backup site'], false, 'The given data was invalid', 422);
          }



          $currentService = ServiceConfigBackup::where('id', $customerBackup->service_id)->where("ocs_charge", $resServiceIDBackup->ocs_charge)->first();
          if (!$currentService) {
            Log::info("$enterprise Change product code  to  $request->new_product_code The Product Code incompatible OCS Charge type with previous package on Backup server ");

            //            return $this->ApiReturn(['new_product_code' => 'The Product Code incompatible OCS Charge type with previous package on Backup server'], false, 'The given data was invalid', 422);
          }

        }
        $isRunOnBackup= false;
        if($customer->server_profile!=config("server.server_profile"))
        {

          $isRunOnBackup= true;
        }


      DB::beginTransaction();
      if($BACKUPSTATE)
      {
        DB::connection("db2")->beginTransaction();
      }
      try {
        // Checkup Cước sản lượng

        Log::info($BACKUPSTATE);


        $quantitySubcriberCheck = DB::select("select q.id from service_config s join quantity_config q on s.id= q.service_config_id where s.product_code =? and q.status=0", [$request->new_product_code]);
        $customerQuantity = QuantitySubcriber::where("service_subcriber_id", $customer->id)->whereIn('status', [0,1])->first();

        if ($BACKUPSTATE) {
        $customerQuantityBackup = QuantitySubcriberBackup::where("service_subcriber_id", $customerBackup->id)->where('status', 1)->first();
          if ($customerQuantity) {

              if(!$customerQuantityBackup)
              {
                DB::rollback();
                DB::connection("db2")->rollback();

                $logDuration= round(microtime(true) * 1000)-$startTime;
                Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
                  $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_FAIL Not found quantity config package on backup server");
                /** @var LOG  $logDuration */
                return $this->ApiReturn(["product_code" => ["Not found quantity config package on backup server"]], false, "Not found quantity package on backup server", 500);

              }

          }

          if ($customerQuantityBackup) {

              if(!$customerQuantity)
              {
                DB::rollback();
                DB::connection("db2")->rollback();

                $logDuration= round(microtime(true) * 1000)-$startTime;
                Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
                  $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_FAIL Not found quantity config package on this server");
                /** @var LOG  $logDuration */

                return $this->ApiReturn(["product_code" => ["Not found quantity config package on this server"]], false, "Not found quantity package on this server", 500);

              }

          }


        }


          $begin_charge_date = date_create(date('Y-m-d H:i:s'));
          date_modify($begin_charge_date, "+".config("sbc.delay_quantity_charge_in_minutes")." minutes");


          if(count($quantitySubcriberCheck)==1)
          {



            $newQuantity=new QuantitySubcriber();
            $newQuantity->service_subcriber_id= $customer->id;
            QuantitySubcriber::where("service_subcriber_id", $customer->id)->update(['status'=>1, 'resub'=>0]);
            if($isRunOnBackup )
            {
              // Disable all
              $newQuantity->status= 1;
              $newQuantity->resub= 0;

            }
            else
            {

              $newQuantity->status= 0;
              $newQuantity->resub= 1;

            }

            $newQuantity->begin_use_date= $begin_charge_date;
            $newQuantity->quantity_config_id= $quantitySubcriberCheck[0]->id;
            $newQuantity->save();

            Log::info("AUTO ADD QUANTITY SUBSCRIBER PACKAGE");
            Log::info(json_encode($newQuantity));

            if($customerQuantity)
            {
              $customerQuantity->status=1; // Hủy gói cũ
              $customerQuantity->resub=0; // Hủy gói cũ
              $customerQuantity->save();
            }


          }




          if($BACKUPSTATE)
          {

            $quantitySubcriberCheckBackup=
              DB::connection("db2")->select("select q.id from service_config s join quantity_config q on s.id= q.service_config_id where s.product_code =? and q.status=0",[$request->new_product_code]);

            QuantitySubcriberBackup::where("service_subcriber_id", $customerBackup->id)->update(['status'=>1, 'resub'=>0]); // Disable gói cước cũ

            if(count($quantitySubcriberCheckBackup)==1)
            {

              $newQuantityBackup=new QuantitySubcriberBackup();
              $newQuantityBackup->service_subcriber_id= $customerBackup->id;

              if($isRunOnBackup)
              {

                $newQuantityBackup->status= 0;
                $newQuantityBackup->resub= 1;
              }
              else
              {

                $newQuantityBackup->status= 1;
                $newQuantityBackup->resub= 0;

              }
              $newQuantityBackup->begin_use_date= $begin_charge_date;
              $newQuantityBackup->quantity_config_id= $quantitySubcriberCheckBackup[0]->id;
              $newQuantityBackup->save();
              Log::info("AUTO ADD QUANTITY SUBSCRIBER PACKAGE ON BACKUP");
              Log::info(json_encode($newQuantityBackup));
              if($customerQuantityBackup)
              {
                $customerQuantityBackup->status=1; // Hủy gói cũ
                $customerQuantityBackup->resub=0; // Hủy gói cũ
                $customerQuantityBackup->save();
              }
            }


          }


        // Cập nhật dịch vụ



        DB::table('service_subcriber')
          ->where('enterprise_number',$enterprise)
          ->update(['service_config_id' => $resServiceID->id, 'updated_at' => date("Y-m-d H:i:s")]);

        // Update Use brandname
        Hotlines::where("cus_id",$customer->id)->update(['use_brand_name'=>$use_brand_name,'ocs_charge'=>$resServiceID->ocs_charge]);

        // Backup Site

        Customers::where('id',$customer->id)
          ->update(['service_id'=>$resServiceID->id, 'updated_at' => date("Y-m-d H:i:s")]);



        if($BACKUPSTATE)
        {

         CustomersBackup::where('id',$customerBackup->id)
           ->update(['service_id'=>$resServiceIDBackup->id, 'updated_at' => date("Y-m-d H:i:s")]);

          DB::connection("db2")->table('service_subcriber')
            ->where('enterprise_number',$enterprise)
            ->update(['service_config_id' => $resServiceIDBackup->id, 'updated_at' => date("Y-m-d H:i:s")]);

          // Update Use brandname
          HotlinesBackup::where("cus_id",$customerBackup->id)->update(['use_brand_name'=>$use_brand_name,'ocs_charge'=>$resServiceIDBackup->ocs_charge]);

          $this->SetActivity($validData,'customers',$customerBackup->id, 0, config("sbc.action.change_product_code"),"[BACKUPSITE] Thay đổi gói cước thành ". $request->new_product_code, $enterprise,null);
        }
        Log::info("CUSTOMER");
        Log::info(json_encode($customer));

        $customer->save();
        // END

        $this->SetActivity($validData,'customers',$customer->id, 0, config("sbc.action.change_product_code"),"Thay đổi gói cước thành ". $request->new_product_code, $enterprise,null);
        // TODO SET CDR ACTIVITY


        $CDR= "$enterprise|3|".date('YmdHis')."|$request->new_product_code";
        $this->CDRActivity($customer->server_profile, $CDR, $enterprise, $API_STATE."CHANGE_PRODUCT_CODE");

        DB::commit();
        if($BACKUPSTATE)
        {
          DB::connection("db2")->commit();
        }

      }
      catch (\Exception $exception)
      {

        DB::rollback();
        if($BACKUPSTATE)
        {
          DB::connection("db2")->rollback();
        }

        Log::info(json_encode($exception->getTraceAsString()));

        return $this->ApiReturn($exception->getTraceAsString(), false, "ERROR", 500);

      }

      /** @var LOG  $logDuration */
      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
        $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_SUCCESS");
      /** @var LOG  $logDuration */


      return $this->ApiReturn(null, true, null, 200);
    }

    public function changeCustomersStatus(Request $request)
    {
      $startTime= round(microtime(true) * 1000);
      $user= $request->user;
      $validData = $request->only('enterprise_number', 'new_status','reason');
      $BACKUPSTATE=$request->single_mode==1?false:config("server.backup_site");
      $API_STATE= $request->api_source?"API|":"WEB|";

      if (!$this->checkEntity($user->id, "CHANGE_CUSTOMER_STATUS")) {
        Log::info($user->email . '  TRY TO GET V1CustomerController.changeCustomersStatus WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }




      $validator = Validator::make($validData, [
            'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
            'new_status' => 'required|in:0,1',
            'reason'=>'nullable|unicode_valid|max:200'
        ]);
        if ($validator->fails()) {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|CHANGE_CUSTOMERS_STATUS|".$logDuration."|CHANGE_FAIL INVALID DATA");
          return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }

        $newStatus=$request->new_status;
        $enterprise= $request->enterprise_number;

        $reason= request('reason'," ");

      $cus=  Customers::where('enterprise_number',$enterprise )
        ->whereIn('blocked',[0,1])
        ->first();
      if(!$cus)
      {
       return $this->ApiReturn([],false, "Not found active enterprise number ".$enterprise, 403);
      }
      $service= ServiceConfig::where("id",$cus->service_id)->first();
      if ($BACKUPSTATE) {
        $cusBackup = CustomersBackup::where('enterprise_number', $enterprise)->whereIn('blocked', [0, 1])->first();

        if (!$cusBackup) {
          return $this->ApiReturn([], false, "Not found active enterprise number on all site " . $enterprise, 403);
        }
        $serviceBackup = ServiceConfigBackup::where("id", $cusBackup->service_id)->first();
      }
      $IS_BACKUP=$cus->server_profile==config("server.server_profile_backup")?true:false;

      DB::beginTransaction();
      if($BACKUPSTATE)
      {
        DB::connection("db2")->beginTransaction();
      }
      try{
        $reason= $request->reason?$request->reason:"CHANGE_CUSTOMER_STATUS_NO_REASON";
        $productCode= ServiceConfig::where("id",$cus->service_id)->first();


        if(!$IS_BACKUP) // Nếu không phải là backup hoặc trạng thái mới là 1 thì khóa luôn
          {

              // CẤU HÌNH ACTIVITY BỎ QUA
            $fromState= $cus->blocked==1?"Đang chặn 2 chiều":"Đang hoạt động";
            $toState=$newStatus==1?"Chặn 2 chiều":"Khôi phục hoạt động";
            $this->SetActivity($validData, "customers",$cus->id, 0, config("sbc.action.pause_state_customer"),"Thay đổi trạng thái khách hàng từ ". $fromState. " thành ". $toState, $enterprise,null);
            $cus->blocked=$newStatus;
            $cus->updated_at= date("Y-m-d H:i:s");
            $cus->pause_state="10";
            if($newStatus==0)
            {
              $CDR= $enterprise."|0|".date("YmdHis")."|".$service->product_code;
              $this->CDRActivity($cus->server_profile,$CDR, $enterprise,$API_STATE."OPEN_CUSTOMER");
            }
            else
            {

                           $CDR= $enterprise."|1|".date("YmdHis")."|".$service->product_code;
              $this->CDRActivity($cus->server_profile,$CDR, $enterprise,$API_STATE."PAUSE_CUSTOMER");
            }

            $this->addCustomerChangeStateLog($request->enterprise_number, $cus->id,$reason, $request->new_status, $user->id);


            // Thay đổi cấu hình trên con chính

            DB::table('service_subcriber')
              ->where('enterprise_number', $enterprise)
              ->update(['status' => $newStatus, 'updated_at' => date("Y-m-d H:i:s")]);
            // DISABLE HOTLINE


            $hotlines = DB::table('hot_line_config')
              ->where('cus_id', $cus->id)
              ->whereIn('status',[0,1])
              ->select('hotline_number', 'id','status')
              ->get();

              $sqlBuilderYckhSBC = "";
              $sqlBuilderYckhHotlineConfig = "";

            if ($newStatus == 0) {
              if ($reason == config('hotline.khyc')) {
                $ress = HotlineStatusLog::where('cus_id', $cus->id)->where('reason', config('hotline.khyc'))->delete(); // Xóa bản ghi KHYC

                $lstHotline = HotlineStatusLog::where('cus_id', $cus->id)->select('id', 'hotline_number', 'hotline_id')->where('reason', '!=', config('hotline.khyc'))->get();

                foreach ($lstHotline as $line) {
                  $sqlBuilderYckhSBC .= " and ((ifNull(caller,'') !=$line->hotline_number) AND (ifNull(callee,'') !=$line->hotline_number)) ";
                  $sqlBuilderYckhHotlineConfig .= " and  id != $line->hotline_id ";
                }
              } else {
                $ress = HotlineStatusLog::where('cus_id', $cus->id)->where('reason', '!=', config('hotline.khyc'))->delete(); // Xóa bản ghi không phải KHYC

                $lstHotline = HotlineStatusLog::where('cus_id', $cus->id)->where('reason', !config('hotline.khyc'))->select('id', 'hotline_number', 'hotline_id')->get();

                foreach ($lstHotline as $line) {
                  $sqlBuilderYckhSBC .= " and ((ifNull(caller,'') !=$line->hotline_number) AND (ifNull(callee,'') !=$line->hotline_number)) ";
                  $sqlBuilderYckhHotlineConfig .= " and  id != $line->hotline_id ";
                }
              }
            } else {
              if ($reason ==config('hotline.khyc'))
              {
                foreach ($hotlines as $line) {
                  $res = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'))->get();

                  if (count($res) > 0) {
                    foreach ($res as $log) {
                      HotlineStatusLog::where('id', $log->id)->update(["updated_at" => date("Y-m-d H:i:s"), 'reason' => $reason]);
                    }
                  } else {
                    HotlineStatusLog::insert(
                      [
                        'hotline_id' => $line->id,
                        'cus_id' => $cus->id,
                        'hotline_number' => $line->hotline_number,
                        'enterprise_number' => $enterprise,
                        'reason'=>$reason,
                        'pause_state'=>11,
                        'user_id' => $user->id]);
                    HotlineStatusLog::insert(
                      [
                        'hotline_id' => $line->id,
                        'cus_id' => $cus->id,
                        'hotline_number' => $line->hotline_number,
                        'enterprise_number' => $enterprise,
                        'reason'=>$reason,
                        'pause_state'=>12,
                        'user_id' => $user->id]);
                  }
                }
              }
              else
              {
                foreach ($hotlines as $line) {
                  $res = HotlineStatusLog::where('hotline_id', $line->id)->where('reason','!=', config('hotline.khyc'))->get();

                  if (count($res) > 0) {
                    foreach ($res as $log) {
                      HotlineStatusLog::where('id', $log->id)->update(["updated_at" => date("Y-m-d H:i:s"), 'reason' => $reason]);
                    }
                  }else {
                    HotlineStatusLog::insert(
                      [
                        'hotline_id' => $line->id,
                        'cus_id' => $cus->id,
                        'hotline_number' => $line->hotline_number,
                        'enterprise_number' => $enterprise,
                        'reason'=>$reason,
                        'pause_state'=>11,
                        'user_id' => $user->id]);

                    HotlineStatusLog::insert(
                      [
                        'hotline_id' => $line->id,
                        'cus_id' => $cus->id,
                        'hotline_number' => $line->hotline_number,
                        'enterprise_number' => $enterprise,
                        'reason'=>$reason,
                        'pause_state'=>12,
                        'user_id' => $user->id]);
                  }
                }
              }
            }

              $sqlUpdateSbcRouting = "update sbc.routing set status= ? where i_customer=? ";
              $paramUpdate = [$newStatus, $cus->id];
              if ($sqlBuilderYckhSBC) {
                $sqlUpdateSbcRouting .= $sqlBuilderYckhSBC;
              }
              DB::update($sqlUpdateSbcRouting, $paramUpdate);

              $sqlUpdateHotline = " update hot_line_config set status= ?, pause_state=10 where cus_id=? and status in (0,1) ";
              if ($sqlBuilderYckhHotlineConfig) {
                $sqlUpdateHotline .= $sqlBuilderYckhHotlineConfig;
              }

              DB::update($sqlUpdateHotline, $paramUpdate);


              // END UPDATE YCKH


            foreach ($hotlines as $line) {

              $CDR= $enterprise."|".$request->new_status."|".date("YmdHis")."|".$line->hotline_number;
              $this->CDRActivity($cus->server_profile,$CDR, $enterprise,$API_STATE."PAUSE_STATE_HOTLINE");
              $this->SetActivity($validData, "hot_line_config",$cus->id, 0, config("sbc.action.pause_state_hotline"),"Thay đổi trạng thái khách hàng từ ". $fromState. " thành ". $toState, $enterprise,$line->hotline_number);

            }

            if($BACKUPSTATE)
            {
              $hotlinesBackup = DB::connection("db2")->table('hot_line_config')
                ->where('cus_id', $cusBackup->id)
                ->whereIn('status',[0,1])
                ->select('hotline_number', 'id','status')
                ->get();

              DB::connection("db2")->table('service_subcriber')
                ->where('enterprise_number', $enterprise)
                ->update(['status' => 1, 'updated_at' => date("Y-m-d H:i:s")]);


              // DISABLE HOTLINE



              // FIX YCKK ON BACKUP ---

              $sqlBuilderYckhSBCBackup = "";
              $sqlBuilderYckhHotlineConfigBackup = "";

              if ($newStatus == 0) {
                if ($reason == config('hotline.khyc')) {
                  $ress = HotlineStatusLogBackup::where('cus_id', $cusBackup->id)->where('reason', config('hotline.khyc'))->delete(); // Xóa bản ghi KHYC

                  $lstHotline = HotlineStatusLogBackup::where('cus_id', $cusBackup->id)->select('id', 'hotline_number', 'hotline_id')->where('reason', '!=', config('hotline.khyc'))->get();

                  foreach ($lstHotline as $line) {
                    $sqlBuilderYckhSBCBackup .= " and ((ifNull(caller,'') !=$line->hotline_number) AND (ifNull(callee,'') !=$line->hotline_number)) ";
                    $sqlBuilderYckhHotlineConfigBackup .= " and  id != $line->hotline_id ";
                  }
                } else {
                  $ress = HotlineStatusLogBackup::where('cus_id', $cusBackup->id)->where('reason', '!=', config('hotline.khyc'))->delete(); // Xóa bản ghi không phải KHYC

                  $lstHotline = HotlineStatusLogBackup::where('cus_id', $cusBackup->id)->where('reason', !config('hotline.khyc'))->select('id', 'hotline_number', 'hotline_id')->get();

                  foreach ($lstHotline as $line) {
                    $sqlBuilderYckhSBCBackup .= " and ((ifNull(caller,'') !=$line->hotline_number) AND (ifNull(callee,'') !=$line->hotline_number)) ";
                    $sqlBuilderYckhHotlineConfigBackup .= " and  id != $line->hotline_id ";
                  }
                }
              } else {
                if ($reason ==config('hotline.khyc'))
                {
                  foreach ($hotlinesBackup as $line) {
                    $res = HotlineStatusLogBackup::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'))->get();

                    if (count($res) > 0) {
                      foreach ($res as $log) {
                        HotlineStatusLogBackup::where('id', $log->id)->update(["updated_at" => date("Y-m-d H:i:s"), 'reason' => $reason]);
                      }
                    } else {
                      HotlineStatusLogBackup::insert(
                        [
                          'hotline_id' => $line->id,
                          'cus_id' => $cusBackup->id,
                          'hotline_number' => $line->hotline_number,
                          'enterprise_number' => $enterprise,
                          'reason'=>$reason,
                          'pause_state'=>11,
                          'user_id' => $user->id]);
                      HotlineStatusLogBackup::insert(
                        [
                          'hotline_id' => $line->id,
                          'cus_id' => $cusBackup->id,
                          'hotline_number' => $line->hotline_number,
                          'enterprise_number' => $enterprise,
                          'reason'=>$reason,
                          'pause_state'=>12,
                          'user_id' => $user->id]);
                    }
                  }
                }
                else
                {
                  foreach ($hotlines as $line) {
                    $res = HotlineStatusLogBackup::where('hotline_id', $line->id)->where('reason','!=', config('hotline.khyc'))->get();

                    if (count($res) > 0) {
                      foreach ($res as $log) {
                        HotlineStatusLogBackup::where('id', $log->id)->update(["updated_at" => date("Y-m-d H:i:s"), 'reason' => $reason]);
                      }
                    }else {
                      HotlineStatusLogBackup::insert(
                        [
                          'hotline_id' => $line->id,
                          'cus_id' => $cusBackup->id,
                          'hotline_number' => $line->hotline_number,
                          'enterprise_number' => $enterprise,
                          'reason'=>$reason,
                          'pause_state'=>11,
                          'user_id' => $user->id]);

                      HotlineStatusLogBackup::insert(
                        [
                          'hotline_id' => $line->id,
                          'cus_id' => $cusBackup->id,
                          'hotline_number' => $line->hotline_number,
                          'enterprise_number' => $enterprise,
                          'reason'=>$reason,
                          'pause_state'=>12,
                          'user_id' => $user->id]);
                    }
                  }
                }
              }




              if($newStatus==1)
              {


                DB::connection("db2")->table('sbc.routing')
                  ->where('i_customer', $cusBackup->id)

                  ->update(['status' => $request->new_status]);
                DB::connection("db2")
                  ->table('hot_line_config')
                  ->where('cus_id', $cusBackup->id)
                  ->whereIn('status', [0, 1])
                  ->update(['status' => $request->new_status,'pause_state'=>10]);
                $cusBackup->pause_state=10;
              }
              else
              {

                DB::connection("db2")
                  ->table('hot_line_config')
                  ->where('cus_id', $cusBackup->id)
                  ->whereIn('status', [0, 1])
                  ->update(['status' => 1,'pause_state'=>11]);

                foreach ($hotlinesBackup as $line)
                {
                  DB::connection("db2")->table('sbc.routing')
                    ->where('i_customer', $cusBackup->id)
                    ->where('caller', $line->hotline_number)
                    ->update(['status' => 1]); // Khóa chiều gọi vào
                  DB::connection("db2")->table('sbc.routing')
                    ->where('i_customer', $cusBackup->id)
                    ->where('callee', $line->hotline_number)
                    ->update(['status' => 0]); // Mở chiều gọi ra
                }

                $cusBackup->pause_state=11;
              }
              $cusBackup->blocked=1;
              $cusBackup->save();

            }



            $cus->save();
          } else {
          // Backup Case

          Log::info("RUNNING ON SECONDARY");
          $fromState= $cus->blocked==1?"Đang chặn 2 chiều":"Đang hoạt động";
          $toState=$newStatus==1?"Chặn 2 chiều":"Khôi phục hoạt động";
          $this->SetActivity([], "customers",$cusBackup->id, 0, config("sbc.action.pause_state_customer"),"[BACKUPSITE] Thay đổi trạng thái khách hàng từ ". $fromState. " thành ". $toState, $enterprise,null);
          $cusBackup->blocked=$newStatus;
          $cusBackup->updated_at= date("Y-m-d H:i:s");
          $cusBackup->pause_state="10";

          if($newStatus==0)
          {

            $CDR= $enterprise."|$newStatus|".date("YmdHis")."|".$serviceBackup->product_code;
            $this->CDRActivity($cus->server_profile,$CDR, $enterprise,$API_STATE."OPEN_STATE_CUSTOMER");
          }
          else
          {

            $CDR= $enterprise."|$newStatus|".date("YmdHis")."|".$serviceBackup->product_code;
            $this->CDRActivity($cus->server_profile,$CDR, $enterprise,$API_STATE."PAUSE_STATE_CUSTOMER");
          }


          $this->addCustomerChangeStateLog($request->enterprise_number, $cusBackup->id,$reason, $request->new_status, $user->id);




          DB::connection("db2")->table('service_subcriber')
            ->where('enterprise_number', $enterprise)
            ->update(['status' => $newStatus, 'updated_at' => date("Y-m-d H:i:s")]);
          // DISABLE HOTLINE
          $hotlines = DB::connection("db2")->table('hot_line_config')
            ->where('enterprise_number', $request->enterprise_number)
            ->whereIn('status',[0,1])
            ->select('hotline_number', 'id','status')
            ->get();





          $sqlBuilderYckhSBC = "";
          $sqlBuilderYckhHotlineConfig = "";

          if ($newStatus == 0) {
            if ($reason == config('hotline.khyc')) {
              HotlineStatusLogBackup::where('cus_id', $cusBackup->id)->where('reason', config('hotline.khyc'))->delete();
              $lstHotline = HotlineStatusLogBackup::where('cus_id', $cusBackup->id)->where('reason', '!=',config('hotline.khyc'))->select('id', 'hotline_number', 'hotline_id')->get();

              foreach ($lstHotline as $line) {
                $sqlBuilderYckhSBC .= " and ((ifNull(caller,'') !=$line->hotline_number) AND (ifNull(callee,'') !=$line->hotline_number)) ";
                $sqlBuilderYckhHotlineConfig .= " and  id != $line->hotline_id ";
              }
            } else {
              HotlineStatusLogBackup::where('cus_id', $cusBackup->id)->where('reason','!=', config('hotline.khyc'))->delete();
              $lstHotline = HotlineStatusLogBackup::where('cus_id', $cusBackup->id)->where('reason',config('hotline.khyc'))->select('id', 'hotline_number', 'hotline_id')->get();

              foreach ($lstHotline as $line) {
                $sqlBuilderYckhSBC .= " and ((ifNull(caller,'') !=$line->hotline_number) AND (ifNull(callee,'') !=$line->hotline_number)) ";
                $sqlBuilderYckhHotlineConfig .= " and  id != $line->hotline_id ";
              }
            }
          } else { //
            if ($reason == config('hotline.khyc')) // Khóa theo KHYC
            {
              foreach ($hotlines as $line) {
                $res = HotlineStatusLogBackup::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'))->first();
                if ($res) {
                  $res->updated_at = date("Y-m-d H:i:s");
                  $res->save();
                } else {
                  HotlineStatusLogBackup::insert(['hotline_id' => $line->id, 'cus_id' => $cusBackup->id,
                    'hotline_number' => $line->hotline_number,
                    'reason'=>$reason,
                    'enterprise_number' => $enterprise, 'user_id' => $user->id]);
                }
              }
            }
            else
            {
              foreach ($hotlines as $line) {
                $res = HotlineStatusLogBackup::where('hotline_id', $line->id)->where('reason','!=' , config('hotline.khyc'))->first();
                if ($res) {
                  $res->updated_at = date("Y-m-d H:i:s");
                  $res->reason= $reason;
                  $res->save();
                } else {
                  HotlineStatusLogBackup::insert(['hotline_id' => $line->id, 'cus_id' => $cusBackup->id,
                    'hotline_number' => $line->hotline_number,
                    'enterprise_number' => $enterprise,
                    'user_id' => $user->id,
                    'reason'=>$reason

                  ]);
                }
              }
            }
          }

          $sqlUpdateSbcRouting = "update sbc.routing set status= ? where i_customer=? ";
          $paramUpdate = [$newStatus, $cusBackup->id];
          if ($sqlBuilderYckhSBC) {
            $sqlUpdateSbcRouting .= $sqlBuilderYckhSBC;
          }
          DB::connection("db2")->update($sqlUpdateSbcRouting, $paramUpdate);


          $sqlUpdateHotline = " update hot_line_config set status= ?, pause_state=10 where cus_id=? and status in (0,1) ";
          if ($sqlBuilderYckhHotlineConfig) {
            $sqlUpdateHotline .= $sqlBuilderYckhHotlineConfig;
          }

          DB::connection("db2")->update($sqlUpdateHotline, $paramUpdate);


          foreach ($hotlines as $line) {

            $CDR= $enterprise."|".$request->new_status."|".date("YmdHis")."|".$line->hotline_number;
            $this->CDRActivity($cus->server_profile,$CDR, $enterprise,$API_STATE."PAUSE_STATE_HOTLINE");
            $this->SetActivity($validData, "hot_line_config",$cusBackup->id, 0, config("sbc.action.pause_state_hotline"),"[BACKUPSITE] Thay đổi trạng thái khách hàng từ ". $fromState. " thành ". $toState, $enterprise,$line->hotline_number);

          }





            DB::table('service_subcriber')
              ->where('enterprise_number', $enterprise)
              ->update(['status' => 1, 'updated_at' => date("Y-m-d H:i:s")]);
            // DISABLE HOTLINE
          $hotlineMain = DB::table('hot_line_config')
              ->where('enterprise_number', $request->enterprise_number)
              ->whereIn('status',[0,1])
              ->select('hotline_number', 'id','status')
              ->get();





          if ($newStatus == 0) {
            if ($reason == config('hotline.khyc')) {
              HotlineStatusLog::where('cus_id', $cus->id)->where('reason',config('hotline.khyc'))->delete();
            }
            else
            {
              HotlineStatusLog::where('cus_id', $cus->id)->where('reason','!=',config('hotline.khyc'))->delete();
            }
          } else { //
            if ($reason == config('hotline.khyc')) // Khóa theo KHYC
            {
              foreach ($hotlineMain as $line) {
                $res = HotlineStatusLog::where('hotline_id', $line->id)->where('reason',config('hotline.khyc'))->first();
                if ($res) {
                  $res->updated_at = date("Y-m-d H:i:s");
                  $res->save();
                } else {
                  HotlineStatusLog::insert(['hotline_id' => $line->id, 'cus_id' => $cus->id,
                    'hotline_number' => $line->hotline_number,
                    'enterprise_number' => $enterprise,
                    'reason'=>$reason,
                    'user_id' => $user->id]);
                }
              }
            }
            else{
              foreach ($hotlineMain as $line) {
                $res = HotlineStatusLog::where('hotline_id', $line->id)->where('reason','!=',config('hotline.khyc'))->first();
                if ($res) {
                  $res->updated_at = date("Y-m-d H:i:s");
                  $res->save();
                } else {
                  HotlineStatusLog::insert(['hotline_id' => $line->id, 'cus_id' => $cus->id,
                    'hotline_number' => $line->hotline_number,
                    'enterprise_number' => $enterprise,
                    'reason'=>$reason,
                    'user_id' => $user->id]);
                }
              }
            }
          }
          // FIX YCKK ON BACKUP ---




          if($newStatus==1)
            {
              DB::table('sbc.routing')
                ->where('i_customer', $cus->id)
                ->update(['status' => $request->new_status]);
              DB::table('hot_line_config')
                ->where('cus_id',$cus->id)
                ->whereIn('status', [0, 1])
                ->update(['status' => $request->new_status,'pause_state'=>10]);
              $cus->pause_state=10;

            }
            else
            {
              $cus->pause_state=11;

              DB::table('hot_line_config')
                ->where('cus_id', $cus->id)
                ->whereIn('status', [0, 1])
                ->update(['status' => 1,'pause_state'=>11]);

              foreach ($hotlineMain as $line) {
                DB::table('sbc.routing')
                  ->where('i_customer', $cus->id)
                  ->where('caller', $line->hotline_number)
                  ->update(['status' => 1]);
                DB::table('sbc.routing')
                  ->where('i_customer', $cus->id)
                  ->where('callee', $line->hotline_number)
                  ->update(['status' => 0]); // Mở chiều gọi ra
              }

            }


          $cus->blocked=1;
          // Enable charge_fee-limit

          $cusBackup->save();
          $cus->save();

        }





        if($BACKUPSTATE)
        {
          DB::connection("db2")->commit();
        }
          DB::commit();

        $logDuration= round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($hotlines)."|CHANGE_CUSTOMERS_STATUS|".$logDuration."|HOTLINES_CHANGED");

      }
      catch (\Exception $exception)
      {
        if($BACKUPSTATE)
        {
          DB::connection("db2")->rollback();
        }
          DB::rollback();

        $logDuration= round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|CHANGE_CUSTOMERS_STATUS|ERROR|");
        Log::info(($exception->getTraceAsString()));
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|CHANGE_CUSTOMERS_STATUS|".$logDuration."|CHANGE_ERROR");

        return $this->ApiReturn([], false, 'INTERNAL SERVER ERROR', 500);

      }


      return $this->ApiReturn(null, true, null, 200);
    }



    public function changeCustomerIdentity(Request $request)
    {
   // DONE BACKUP
      $startTime= round(microtime(true) * 1000);
        $user= $request->user;
      if (!$this->checkEntity($user->id, "CHANGE_CUSTOMER_ENTERPRISE_NUMBER")) {
        Log::info($user->email . '  TRY TO GET V1CustomerController.changeCustomerIdentity WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }


        $validData = $request->only('enterprise_number', 'new_enterprise_number');
        $validator = Validator::make($validData, [
            'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
            'new_enterprise_number' => 'required|alpha_dash|min:5|max:250|unique:customers,enterprise_number'
        ]);

        if ($validator->fails()) {

          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|CHANGE_ENTERPRISE_NUMBER|".$logDuration."|CHANGE_FAIL INVALID DATA");

          return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }

      $customer=Customers::where('enterprise_number', $request->enterprise_number)
        ->whereIn("blocked",[0,1])
        ->first();

      $newEnterPriseNumber=$request->new_enterprise_number;
      $enterprise=$request->enterprise_number;





      if(!$customer)
      {
        return $this->ApiReturn(["enteprise_number"=>["The enterprise number fields is invalid"]],false, "Enterprise not active or not found",404);
      }



      if(config("server.backup_site"))
      {
        $customerBackup= CustomersBackup::where('enterprise_number', $enterprise)->whereIn('blocked',[0,1])->first();

        if(!$customerBackup)
        {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".
            $request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PRODUCTCODE|".$logDuration."|CHANGE_CUSTOMER_PRODUCT_CODE_FAIL Invalid enterprise number");
          /** @var LOG  $logDuration */

          return $this->ApiReturn(['enterprise_number' => 'Enterprise number not found on backup site'], false, 'The given data was invalid', 422);
        }

      }


      $this->SetActivity($validData,'customers', $customer->id, 0,config("sbc.action.change_enterprise_number"), "Đổi số đại diện từ ".$enterprise." sang ".$newEnterPriseNumber." ", $enterprise, null);


      DB::beginTransaction();
      if(config("server.backup_site"))
      {
        DB::connection("db2")->beginTransaction();
      }

        try{

        DB::table('customers')
            ->where('id', $customer->id)
            ->update(['enterprise_number' => $request->new_enterprise_number, 'updated_at' => date("Y-m-d H:i:s")]);

           DB::table('charge_fee_limit')
            ->where('enterprise_number', $request->enterprise_number)
            ->update(['enterprise_number' => $request->new_enterprise_number]);



        DB::table('users')
            ->where('email', $request->enterprise_number)
            ->update(['email' => $request->new_enterprise_number, 'updated_at' => date("Y-m-d H:i:s")]);

          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|CHANGE_BILLING_ACCOUNT_TO_NEW_ENTERPRISE");




        DB::table('service_subcriber')
            ->where('id', $customer->id)
            ->update(['enterprise_number' => $request->new_enterprise_number, 'updated_at' => date("Y-m-d H:i:s")]);
        $zeroEnterprise = $this->removeZero($request->enterprise_number);
        $zeroNewEnterprise = $this->removeZero($request->new_enterprise_number);
        DB::table('hot_line_config')
            ->where('cus_id', $customer->id)
            ->update(['enterprise_number' => $request->new_enterprise_number, 'updated_at' => date("Y-m-d H:i:s")]);

        DB::table('call_fee_cycle_status')
            ->where('enterprise_number', $zeroEnterprise)
            ->update(['enterprise_number' => $zeroNewEnterprise, 'updated_at' => date("Y-m-d H:i:s")]);
        DB::table('quantity_subcriber_cycle_status')
            ->where('enterprise_number', $zeroEnterprise)
            ->update(['enterprise_number' => $zeroNewEnterprise, 'updated_at' => date("Y-m-d H:i:s")]);
        DB::table('sms_fee_cycle_status')
            ->where('enterprise_number', $zeroEnterprise)
            ->update(['enterprise_number' => $zeroNewEnterprise, 'updated_at' => date("Y-m-d H:i:s")]);
        DB::table('subcharge_fee_cycle_status')
            ->where('enterprise_number', $zeroEnterprise)
            ->update(['enterprise_number' => $zeroNewEnterprise, 'updated_at' => date("Y-m-d H:i:s")]);


          if(config("server.backup_site"))
          {

            DB::connection("db2")->table('customers')
              ->where('id', $customerBackup->id)
              ->update(['enterprise_number' => $request->new_enterprise_number, 'updated_at' => date("Y-m-d H:i:s")]);

            DB::connection("db2")->table('charge_fee_limit')
              ->where('enterprise_number', $request->enterprise_number)
              ->update(['enterprise_number' => $request->new_enterprise_number]);



            DB::connection("db2")->table('users')
              ->where('email', $request->enterprise_number)
              ->update(['email' => $request->new_enterprise_number, 'updated_at' => date("Y-m-d H:i:s")]);

            Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|CHANGE_BILLING_ACCOUNT_TO_NEW_ENTERPRISE_ONBACKUP");

            DB::connection("db2")->table('service_subcriber')
              ->where('id', $customerBackup->id)
              ->update(['enterprise_number' => $request->new_enterprise_number, 'updated_at' => date("Y-m-d H:i:s")]);
            $zeroEnterprise = $this->removeZero($request->enterprise_number);
            $zeroNewEnterprise = $this->removeZero($request->new_enterprise_number);
            DB::connection("db2")->table('hot_line_config')
              ->where('cus_id', $customerBackup->id)
              ->update(['enterprise_number' => $request->new_enterprise_number, 'updated_at' => date("Y-m-d H:i:s")]);

            DB::connection("db2")->table('call_fee_cycle_status')
              ->where('enterprise_number', $zeroEnterprise)
              ->update(['enterprise_number' => $zeroNewEnterprise, 'updated_at' => date("Y-m-d H:i:s")]);
            DB::connection("db2")->table('quantity_subcriber_cycle_status')
              ->where('enterprise_number', $zeroEnterprise)
              ->update(['enterprise_number' => $zeroNewEnterprise, 'updated_at' => date("Y-m-d H:i:s")]);
            DB::connection("db2")->table('sms_fee_cycle_status')
              ->where('enterprise_number', $zeroEnterprise)
              ->update(['enterprise_number' => $zeroNewEnterprise, 'updated_at' => date("Y-m-d H:i:s")]);
            DB::connection("db2")->table('subcharge_fee_cycle_status')
              ->where('enterprise_number', $zeroEnterprise)
              ->update(['enterprise_number' => $zeroNewEnterprise, 'updated_at' => date("Y-m-d H:i:s")]);

            DB::connection("db2")->commit();
          }


        DB::commit();


        }
        catch (\Exception $exception)
        {
          if(config("server.backup_site"))
          {
            DB::connection("db2")->rollback();
          }
            DB::rollback();
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",$startTime)."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|CHANGE_ENTERPRISE_NUMBER|".$logDuration."|CHANGE_FAIL DB Commit fail, data rollback");
          return $this->ApiReturn($exception, false, 'Failed to change enterprise number', 501);
        }


      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|CHANGE_ENTERPRISE_NUMBER|".$logDuration."|CHANGE_SUCCESS");

      return $this->ApiReturn(null, true, null, 200);
    }

    /**
     * Rollback customer
     * Update December 19 2018
     * Rico Nguyen
     */

    public function rollbackCustomer(Request $request, ActivityController $activityController)
    {
      $startTime= round(microtime(true) * 1000);
        $user = $request->user;

      $BACKUPSTATE=$request->single_mode==1?false:config("server.backup_site");
      $API_STATE= $request->api_source?"API|":"WEB|";


      if (!$this->checkEntity($user->id, "ROLLBACK_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET V1CustomerController.rollbackCustomer WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }



      $validData = $request->only('enterprise_number');
        $validator = Validator::make($validData, [
            'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number'
        ]);
        if ($validator->fails()) {

          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|ROLLBACK_CUSTOMER|".$logDuration."|ROLLBACK_FAIL Invalid Data");

          return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }

        // Check has blocked

        $customer = Customers::where('enterprise_number', $request->enterprise_number)
            ->where("blocked",1)
            ->first();

        if ($BACKUPSTATE) {
          $customerBackup = CustomersBackup::  where('enterprise_number', $request->enterprise_number)->where("blocked", 1)->first();
        }

        if (!$customer) {



          if($BACKUPSTATE)
          {


            if(!$customerBackup)
            {

              Log::info("Customers has been active on backup site . Can not rollback ". $request->enterprise_number);
              $logDuration= round(microtime(true) * 1000)-$startTime;
              Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|ROLLBACK_CUSTOMER|".$logDuration."|ROLLBACK_FAIL Customer actived");
              return $this->ApiReturn(["errors" => ["message" => "Customers has been active. Can not rollback"]], false, 'The given data was invalid', 409);
            }

            if($customerBackup->created_at != $customerBackup->updated_at)
            {
              Log::info("Customers has been active and modify. Can not rollback ". $request->enterprise_number);
              $logDuration= round(microtime(true) * 1000)-$startTime;
              Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|ROLLBACK_CUSTOMER|".$logDuration."|ROLLBACK_FAIL Customer already in use");
              return $this->ApiReturn(["errors" => ["message" => "Customers has been active and modify on Backup site. Can not rollback. Fix it manual"]], false, 'The given data was invalid', 409);

            }


            $logsBackup =  DB::connection("db2")->table("charge_log")
              ->where("cus_id", $customerBackup->id)
              ->first();

            if ($logsBackup) {
              Log::info("Customers has charge log records . Can not rollback  ". $request->id);
              $logDuration= round(microtime(true) * 1000)-$startTime;
              Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|ROLLBACK_CUSTOMER|".$logDuration."|ROLLBACK_FAIL Customer already charge");

              return $this->ApiReturn(["errors" => ["message" => "Customers has charge log records. Can not rollback "]], false, 'The given data was invalid', 409);

            }


          }


            Log::info("Customers has been active. Can not rollback ". $request->enterprise_number);
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|ROLLBACK_CUSTOMER|".$logDuration."|ROLLBACK_FAIL Customer actived");
           return $this->ApiReturn(["errors" => ["message" => "Customers has been active. Can not rollback"]], false, 'The given data was invalid', 409);

        }

      if($customer->created_at!= $customer->updated_at)
      {
        Log::info("Customers has been active and modify. Can not rollback ". $request->enterprise_number);
        $logDuration= round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|ROLLBACK_CUSTOMER|".$logDuration."|ROLLBACK_FAIL Customer already in use");
        return $this->ApiReturn(["errors" => ["message" => "Customers has been active and modify. Can not rollback. Fix it manual"]], false, 'The given data was invalid', 409);

      }


        // Check has log
        $logs = DB::table("charge_log")
            ->where("cus_id", $customer->id)
            ->first();


        if ($logs) {
            Log::info("Customers has charge log records. Can not rollback  ". $request->id);
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|ROLLBACK_CUSTOMER|".$logDuration."|ROLLBACK_FAIL Customer already charge");

          return $this->ApiReturn(["errors" => ["message" => "Customers has charge log records. Can not rollback "]], false, 'The given data was invalid', 409);

        }


      if(config("server.backup_site"))
      {
        DB::connection("db2")->beginTransaction();
      }

      DB::beginTransaction();
      try
      {

        /** 1 Delete From SBC */
        DB::table("sbc.routing")
            ->where("i_customer", $customer->id)
            ->delete();
        Log::info("Delete SBC complete. " . $customer->id);
//        $activityController->AddActivity($validData, 'sbc.routing', $customer->id, 0, 'api/rollbackCustomer');
        /** 2 Delete From users (billing user) */

        DB::table("users")
            ->where("id", $customer->account_id)
            ->delete();
        Log::info("Delete User complete. " . $customer->account_id);
//        $activityController->AddActivity($validData, 'users',$customer->account_id, 0, 'api/rollbackCustomer');
        /** 3 Delete From hotline_config (billing user) */

        DB::table("hot_line_config")
            ->where("cus_id",$customer->id)
            ->delete();
        Log::info("Delete Hotline complete. " . $customer->id);
//        $activityController->AddActivity($validData, 'hot_line_config',$customer->id, 0, 'api/rollbackCustomer');


        DB::table("service_subcriber")
            ->where("id",$customer->id)
            ->delete();

//        $activityController->AddActivity($validData, 'service_subcriber',$customer->id, 0, 'api/rollbackCustomer');
        /** 4 Delete From service_subcriber (billing user) */
        Log::info("Delete quantity_subcriber  complete. " . $customer->id);

        DB::table("quantity_subcriber")
            ->where("service_subcriber_id",$customer->id)
            ->delete();

        Log::info("Delete customers  complete. " . $customer->id);


        DB::table("customers")
          ->where("id",$customer->id)
          ->delete();

        if($BACKUPSTATE && $customerBackup)
        {

          /** 1 Delete From SBC */
          DB::connection("db2")->table("sbc.routing")
            ->where("i_customer", $customerBackup->id)
            ->delete();
          Log::info("Delete SBC complete. " . $customerBackup->id);
          //        $activityController->AddActivity($validData, 'sbc.routing', $customer->id, 0, 'api/rollbackCustomer');
          /** 2 Delete From users (billing user) */

          DB::connection("db2")->table("users")
            ->where("id", $customerBackup->account_id)
            ->delete();
          Log::info("Delete User complete. " . $customerBackup->account_id);
          //        $activityController->AddActivity($validData, 'users',$customer->account_id, 0, 'api/rollbackCustomer');
          /** 3 Delete From hotline_config (billing user) */

          DB::connection("db2")->table("hot_line_config")
            ->where("cus_id",$customerBackup->id)
            ->delete();
          Log::info("Delete Hotline complete. " . $customerBackup->id);
          //        $activityController->AddActivity($validData, 'hot_line_config',$customer->id, 0, 'api/rollbackCustomer');


          DB::connection("db2")->table("service_subcriber")
            ->where("id",$customerBackup->id)
            ->delete();

          //        $activityController->AddActivity($validData, 'service_subcriber',$customer->id, 0, 'api/rollbackCustomer');
          /** 4 Delete From service_subcriber (billing user) */

          DB::connection("db2")->table("customers")
            ->where("id",$customerBackup->id)
            ->delete();

          DB::connection("db2")->table("quantity_subcriber")
            ->where("service_subcriber_id",$customerBackup->id)
            ->delete();

        }
        DB::commit();

        if(config("server.backup_site"))
        {
          DB::connection("db2")->commit();
        }

      }

      catch (\Exception $exception)
      {

        DB::rollback();
        if(config("server.backup_site"))
        {
          DB::connection("db2")->rollback();
        }
        Log::info(json_encode($exception->getTraceAsString()));
        return $this->ApiReturn(null, false, "Error when rollback", 500);

      }




        // Start Rollback Data
        // ---


        // Check exist log

        Log::warning("ROLLBACK SUCCESS ENTERPRISE NUMBER  $request->enterprise_number \n");
      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|ROLLBACK_CUSTOMER|".$logDuration."|ROLLBACK_SUCCESS");


      return $this->ApiReturn(null, true, null, 200);

    }

    /**
     * Canceled customer
     * Update December 19 2018
     * Rico Nguyen
     */

    public function removeCustomer(Request $request)
    {
// DONE BACKUP
      $startTime= round(microtime(true) * 1000);
      $API_STATE= $request->api_source?"API|":"WEB|";
      $BACKUP_STATE=$request->single_mode?false:config("server.backup_site");

        $user= $request->user;
       Log::info("START DELETE CUSTOMER:" . $request->enterprise_number . " BY UID:".$user->id);


      if (!$this->checkEntity($user->id, "REMOVE_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET V1CustomerController.removeCustomer WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
      }





      $validData = $request->only('enterprise_number', 'reason');
        $validator = Validator::make($validData, [
            'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
            'reason'=>'nullable|unicode_valid|max:200'
        ]);
        if ($validator->fails()) {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|DELETE_CUSTOMER|".$logDuration."|DELETE_FAIL Invalid input data ");

          return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
        }

      $customer =Customers::where('enterprise_number', $request->enterprise_number)->whereIn("blocked",[0,1])->first();
      $service =ServiceConfig::where('id', $customer->service_id)->first();




      if(!$customer)
      {
        return $this->ApiReturn([], false, 'Not found active enterprise number', 422);

      }




      $canceledEnterprise_no= $request->enterprise_number."_HUY_".date("Y_m_d_H_i");
      $zeroEnterprise = $this->removeZero($request->enterprise_number);

      $canceledEnterprise_no_Nozero= $zeroEnterprise."_HUY_".date("Y_m_d_H_i");
      $cusId= $customer->id;

      $hotlines = Hotlines::where('cus_id', $cusId)->select('hotline_number', 'id')->get();

      DB::beginTransaction();

      try {

        DB::table("users")->where("id", $customer->account_id)->where("role", 3)->delete();

        Log::info("Delete Billing user: " . $customer->account_id);
//        $this->SetActivity($validData, 'users', $customer->account_id, 0, 'api/removeCustomer','Xóa bỏ tài khoản billing:'.$customer->account_id);
        /** 3 Delete From hotline_config (billing user) */


        // Check avaliable hotline


        DB::table('customers')->where('id', $cusId)->update(['blocked' => 2, 'updated_at' => date("Y-m-d H:i:s"), 'enterprise_number' => $canceledEnterprise_no]);

        DB::table('charge_fee_limit')->where('enterprise_number', $request->enterprise_number)->update(['enterprise_number' => $canceledEnterprise_no]);

        $reason= $request->reason?$request->reason:"REMOVE_CUSTOMER_NO_REASON";
        $this->addCustomerChangeStateLog($request->enterprise_number, $cusId,$reason, 2, $user->id);

        DB::table('service_subcriber')->where('enterprise_number', $request->enterprise_number)->update(['status' => 2, 'updated_at' => date("Y-m-d H:i:s"), 'enterprise_number' => $canceledEnterprise_no]);

        Log::info("Change to CANCEL NUMBER  Enterprise from : " . $request->enterprise_number ." To ". $canceledEnterprise_no);

        //** UPDATE CYCLE OF EXPIRED  */
        DB::table('call_fee_cycle_status')->where('enterprise_number', $zeroEnterprise)
        ->update(['updated_at' => date("Y-m-d H:i:s"), 'enterprise_number' => $canceledEnterprise_no_Nozero]);

        DB::table('sms_fee_cycle_status')->where('enterprise_number', $zeroEnterprise)
        ->update(['updated_at' => date("Y-m-d H:i:s"), 'enterprise_number' => $canceledEnterprise_no_Nozero]);

        DB::table('subcharge_fee_cycle_status')->where('enterprise_number', $zeroEnterprise)
        ->update(['updated_at' => date("Y-m-d H:i:s"), 'enterprise_number' => $canceledEnterprise_no_Nozero]);

        DB::table('services_apps_linked')->where('cus_id', $cusId)
        ->update(['updated_at' => date("Y-m-d H:i:s"), 'enterprise_number' => $canceledEnterprise_no_Nozero,'active'=>0]);

        DB::table('quantity_subcriber_cycle_status')->where('enterprise_number', $zeroEnterprise)
        ->update(['enterprise_number' => $canceledEnterprise_no_Nozero]);

        DB::table('quantity_subcriber')->where('service_subcriber_id', $cusId)
        ->delete();

        SBCCallGroup::where('cus_id', $cusId)->delete();
        Log::info("Change to CANCEL NUMBER ON CYCLE STATUS  Enterprise from : " . $zeroEnterprise ." To ". $canceledEnterprise_no_Nozero);

        //** END  */

        if (count($hotlines) > 0) {
          foreach ($hotlines as $line) {
            DB::table('sbc.routing')->where('i_customer', $cusId)->delete();
            //    DB::table('hot_line_config')->where('hotline_number', $line->hotline_number)->update(['status' => 2, 'sip_config' => null, 'updated_at' => date("Y-m-d H:i:s"), 'enterprise_number' => $canceledEnterprise_no]);
            DB::table('hot_line_config')->where('id', $line->id)->update(['status' => 2, 'sip_config' => null, 'updated_at' => date("Y-m-d H:i:s"), 'enterprise_number' => $canceledEnterprise_no]);

            $CDR = $request->enterprise_number . "|2|" . date("YmdHis") . "|" . $line->hotline_number;
            $this->CDRActivity($customer->server_profile, $CDR, $request->enterprise_number, $API_STATE . "REMOVE_HOTLINE");
            $this->SetActivity($validData, 'hot_line_config', $line->id, 0, config("sbc.action.cancel_hotline"), 'Hủy số hotline: ' . $line->hotline_number, $request->enterprise_number, $line->hotline_number);
          }
        }





        DB::commit();

      } catch (\Exception $exception) {
        Log::info($exception);

        $logDuration= round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|DELETE_CUSTOMER|".$logDuration."|DELETE_FAIL ERROR UPDATE DATABASE, ROLLBACK DATA");

        DB::rollback();

        return $this->ApiReturn(['error' => 'Error cancel customers'], false, 'Error cancel customer -' . $request->enterprise_number, 500);
      }

      $this->SetActivity($validData, 'customers', $cusId, 0, config("sbc.action.cancel_customer"),'Hủy khách hàng: '.$request->enterprise_number, $request->enterprise_number, null );

      $CDR= $request->enterprise_number."|2|".date("YmdHis")."|".$service->product_code;
      $this->CDRActivity($customer->server_profile,$CDR, $request->enterprise_number,$API_STATE."REMOVE_CUSTOMER");
      Log::info("FINISH DELETE CUSTOMER : " . $request->enterprise_number );
      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|DELETE_CUSTOMER|".$logDuration."|DELETE_SUCCESS");


      return $this->ApiReturn(null, true, null, 200);
    }

  public function upgradeEnterpriseToHotline(Request $request) {
    $user = $request->user;
    $BACKUP_STATE = $request->single_mode ? false : config("server.backup_site");
    $API_STATE = $request->api_source ? "API|" : "WEB|";



    if (!$this->checkEntity($user->id, "UPGRADE_ENTERPRISE_TO_HOTLINE")) {
      Log::info($user->email . '  TRY TO GET V1CustomerController.upgradeEnterpriseToHotline WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
    }




    $validData = $request->only('enterprise_number');
    $validator = Validator::make($validData, ['enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number']);
    if ($validator->fails()) {
      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }
    $resCheckHotline = DB::table('hot_line_config')->where('hotline_number', $request->enterprise_number)->whereIn('status', [0, 1])->count();
    if ($resCheckHotline) {
      return $this->ApiReturn(['enterprise_number' => 'Enterprise number is duplicate with other hotline currently running on system, Upgrade fail'], false, 'The given data was invalid', 422);
    }
    $resCustomer = Customers::where('enterprise_number', $request->enterprise_number)->first();
    $isRunOnBackup = $resCustomer->server_profile == config("server.server_profie") ? false : true;

    $dataHotline = ['cus_id' => $resCustomer->id, 'enterprise_number' => $request->enterprise_number, 'hotline_number' => $request->enterprise_number];
    $dataHotline['status'] = $resCustomer->blocked;
    $dataHotline['pause_state'] = 10;
    // Cài đặt  Hotline

    DB::beginTransaction();
    if ($BACKUP_STATE) {
      DB::connection("db2")->beginTransaction();
    }

    try {
      $hotline = Hotlines::create($dataHotline);

      if ($hotline) {
        // Cài đặt sip

        $vendorData = DB::table('sbc.vendors')->where('i_vendor', $request->vendor_id ? $request->vendor_id : 1)->first();

        $resCustomer->isRunOnBackup = $isRunOnBackup;
        $resCustomer->description = $request->enterprise_number;
        $resCustomer->cus_id = $resCustomer->id;
        $resCustomer->vendor = $vendorData;
        $resCustomer->status = $resCustomer->blocked;
        $resCustomer->hotline = $request->enterprise_number;
        $resCustomer->hotline_id = $hotline->id;
        $this->addSipRouting($resCustomer, false);
      }
      $CDR_TEXT = $request->enterprise_number . "|" . config("sbc.CDR.CHANGE") . "|" . date("YmdHis") . "|" . $request->enterprise_number;

      $this->CDRActivity($resCustomer->server_profile, $CDR_TEXT, $request->enterprise_number, $API_STATE . "ADD_HOTLINE");
      if ($BACKUP_STATE) {
        $resCustomerBackup = CustomersBackup::where('enterprise_number', $request->enterprise_number)->first();

        $dataHotlineBackup = ['cus_id' => $resCustomerBackup->id, 'enterprise_number' => $request->enterprise_number, 'hotline_number' => $request->enterprise_number];
        $dataHotlineBackup['status'] = $resCustomerBackup->blocked;
        $dataHotlineBackup['pause_state'] = 11;
        $hotlineBackup = HotlinesBackup::create($dataHotlineBackup);
        if ($hotlineBackup) {
          $vendorDataBackup = DB::connection("db2")->table('sbc.vendors')->where('i_vendor', $request->vendor_id ? $request->vendor_id : 1)->first();
          $resCustomerBackup->hotline = $request->enterprise_number;
          $resCustomerBackup->isRunOnBackup = $isRunOnBackup;
          $resCustomerBackup->description = $request->enterprise_number;
          $resCustomerBackup->cus_id = $resCustomer->id;
          $resCustomerBackup->vendor = $vendorDataBackup;

          $resCustomerBackup->hotline_id = $hotlineBackup->id;
          $resCustomerBackup->status = $resCustomerBackup->status;
          $this->addSipRouting($resCustomerBackup, true);
        }
      }

      DB::commit();
      if ($BACKUP_STATE) {
        DB::connection("db2")->commit();
      }
      Log::info("SUCCESSS UPGRADEENTERPRISETOHOTLINE" . $request->enterprise_number);
    } catch (\Exception $exception) {
      DB::rollback();
      if ($BACKUP_STATE) {
        DB::connection("db2")->rollback();
      }
      Log::info("ERROR UPGRADEENTERPRISETOHOTLINE" . $request->enterprise_number);
      Log::info(json_encode($exception->getTraceAsString()));

      return $this->ApiReturn([], false, "Error convert enterprise to Hotline", 200);
    }

    return $this->ApiReturn(null, true, null, 200);
  }


    public function getCustomersV2(Request $request){


      $startTime=  round(microtime(true) * 1000);



        $user= $request->user;



      if (!$this->checkEntity($user->id, "VIEW_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET getCustomersV2 WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }





      $totalPerPage= $request->count?$request->count:20;
        $page= $request->page?$request->page:1;
        $skip= ($page-1)*$totalPerPage;
        if ($request->query) {
            $query = $request->input('q');
        } else {
            $query = null;
        }

        $qsort=$request->sorting?$request->sorting:'-id';
        $sort=$qsort[0]=='-'?"DESC":"ASC";
        $sortCol= substr($qsort,1);

        $whiteListCol= ['id','cus_name','status','charge_result','enterprise_number','service_name','created_at','updated_at','total'];


      $validData = $request->all();

      $validator = Validator::make($validData,
        [
          'blocked' => 'nullable|in:0,1,2',
          'charge_error' => 'nullable|in:0,1',
          'q' => 'nullable|unicode_valid|max:50',
          'download'=>'nullable|in:0,1'
        ]);
      if ($validator->fails()) {
        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }



      if(!in_array($sortCol, $whiteListCol))
      {
        return $this->ApiReturn(['sort'=>['Sorting is invalid']], false, 'The given data was invalid', 422);
      }
        $blocked=  isset($request->blocked)&& $request->blocked> -1 ? $request->blocked:null;

        $charge_error= $request->charge_error &&$request->charge_error ==1 ?$request->charge_error: null;
        $download= $request->download && $request->download==1 ? true: false;

         $param= [];
            $sql=" SELECT a.companyname, a.blocked as status, a.updated_at, a.created_at, a.pause_state, a.server_profile,
                a.enterprise_number,  b.total_amount total, c.service_name, 
                a.ip_auth, a.ip_proxy,a.ip_auth_backup, a.ip_proxy_backup, a.destination, a.account_id, 
                a.addr, a.email, a.cus_name, a.id, a.telco_destination,  a.operator_telco_id,  a.auto_detect_blocking,
                e.charge_result, e.charge_time as event_occur_time, a.phone1, c.product_code, a.cfu
                FROM customers a
                join hot_line_config on a.id= hot_line_config.cus_id  
                LEFT JOIN 
                (
                SELECT SUM(chotSale) AS total_amount, enterprise_number
                FROM (
                SELECT SUM(total_amount) chotSale, a.enterprise_number
                FROM call_fee_cycle_status a
                WHERE cycle_to > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')
                GROUP BY a.enterprise_number UNION ALL
                SELECT SUM(total_amount) chotSale, a.enterprise_number
                FROM subcharge_fee_cycle_status a
                WHERE cycle_to > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')
                GROUP BY a.enterprise_number UNION ALL
                SELECT SUM(total_amount) chotSale, a.enterprise_number
                FROM sms_fee_cycle_status a
                WHERE cycle_to > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')
                GROUP BY a.enterprise_number
                
                ) b
                GROUP BY enterprise_number
                )
                b ON SUBSTR(a.enterprise_number,2) = b.enterprise_number  
                LEFT  JOIN service_config c ON a.service_id = c.id                
                
                LEFT JOIN 
                ( SELECT  T1.enterprise_num, T1.charge_result, T1.charge_time , cus_id
                FROM
                charge_log T1 
                INNER JOIN
                (
                SELECT MAX(`charge_time`) AS `time`,`enterprise_num`
                FROM charge_log  where  charge_time > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')   and insert_time > DATE_FORMAT(NOW(),'%Y-%m-%-01 00:00:00') and  charge_result <> '0' and charge_result<>''    GROUP BY enterprise_num) T2 ON T1.`enterprise_num` = T2.`enterprise_num` AND T1.`charge_time` = T2.`time`
                WHERE    T1.insert_time > DATE_FORMAT(NOW(),'%Y-%m-%-01 00:00:00')  AND T1.charge_time > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')
                ORDER BY T1.`charge_time` DESC) e                                 
                on a.id = e.cus_id
                WHERE 1=1 ";

                $sqlCount= "SELECT COUNT(*) as total  from (     Select count(*)  total from customers a join hot_line_config on a.id= hot_line_config.cus_id 
                LEFT JOIN 
                ( SELECT  T1.enterprise_num, T1.charge_result, T1.charge_time , cus_id
                FROM
                charge_log T1 
                INNER JOIN
                ( SELECT MAX(`charge_time`) AS `time`,`enterprise_num`
                FROM charge_log  where  charge_time > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')   and insert_time > DATE_FORMAT(NOW(),'%Y-%m-%-01 00:00:00') and  charge_result <> '0' and charge_result<>''    GROUP BY enterprise_num) T2 ON T1.`enterprise_num` = T2.`enterprise_num` AND T1.`charge_time` = T2.`time`
                WHERE    T1.insert_time > DATE_FORMAT(NOW(),'%Y-%m-%-01 00:00:00')  AND T1.charge_time > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')
                ORDER BY T1.`charge_time` DESC) e                                 
                on a.id = e.cus_id
                WHERE 1=1 
";


        if($query)
        {
            $sql.= "  AND  ((hot_line_config.status in (0,1) and hot_line_config.hotline_number =?) OR  a.companyname like ? OR a.enterprise_number like ? or a.email like ? or a.taxcode like ?) ";
            $sqlCount.= "  AND  ((hot_line_config.status in (0,1) and hot_line_config.hotline_number =?)  OR a.companyname like ? OR a.enterprise_number like ? or a.email like ? or a.taxcode like ?) ";
            array_push($param, "$query", "%$query%", "%$query%", "%$query%", "%$query%");

        }

        if($user->role == ROLE_BILLING)
        {
            $sql .=" AND a.account_id =? ";
            $sqlCount .=" AND a.account_id =? ";
            array_push($param, $user->id);

        }

        if($blocked > -1)
        {
            $sql .=" AND a.blocked = ?";
            $sqlCount .=" AND a.blocked = ?";
            array_push($param, $blocked);

        }

        if($charge_error)
        {
            $sql .=" AND e.charge_result <>'0' ";
            $sqlCount .=" AND e.charge_result <>'0' ";

           // $rscount->where('charge_result',$blocked);
        }
      //  return $sql;



        $sql.= " GROUP BY a.id ORDER BY ".$sortCol." ".$sort." ";
        $sqlCount.= " GROUP BY a.id ) a  ";
        $count= DB::select($sqlCount, $param)[0]->total;


        $sql .=" LIMIT ? OFFSET  ? ";
        array_push($param, $totalPerPage, $skip);

        $res= DB::select($sql, $param);

        foreach ($res as $customer) {

            $fee_limit = ChargeFeeLimit::where('enterprise_number', $customer->enterprise_number)->orderBy('updated_at', 'desc')->first();

            $customer->fee_limit = $fee_limit ? $fee_limit->limit_amount : null;

        }

      $logDuration=  round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($request->all())."|GET_CUSTOMER_LIST|".$logDuration."");


        return $this->ApiReturn(['data'=>$res, 'count'=>$count,  'sqlCount'=>$sqlCount,   'user'=>['id'=>$user->id,'role'=>$user->role]],true, null, 200);




    }




    public  function getDownloadCustomer(Request $request)
    {

        $cookie=null;
        $user= null;
        if (isset($_COOKIE["sbc"])) {
            $cookie = $_COOKIE["sbc"];
            $user = $this->getUserByCookie($cookie);
        }
        if (!$user) {
            return "Permission denied";
        }

      if (!$this->checkEntity($user->id, "DOWNLOAD_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET getDownloadCustomer WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $request->user= $user;
        $request->count= config('sbc.limitCustomerDownload');
        $res= $this->getCustomersV2($request)->original->data;

        return view('exportCustomer', ['data'=>$res,'i'=>1]);
    }


    public function serviceAddedCode(Request $request)
    {
      // DONE BACKUP
      $startTime = round(microtime(true) * 1000);

      $user = $request->user;
      Log::info("START ADD_TOS_SERVICE TO:" . $request->enterprise_number . " BY UID:" . $user->id);




      if (!$this->checkEntity($user->id, "UPGRADE_ENTERPRISE_TO_HOTLINE")) {
        Log::info($user->email . '  TRY TO GET V1CustomerController.CONFIG_CUSTOMER WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }




      $postData = $request->only("service_key", "active", "cus_id", "enterprise_number");
      $validator = Validator::make($postData, [
        'enterprise_number' => 'required|exists:customers,enterprise_number',
        'service_key'=>'required|exists:service_config,product_code',
        'active'=>'required|in:0,1'
      ]);
      if ($validator->fails()) {
        $logDuration= round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($postData)."|ADD_TOS_SERVICE|".$logDuration."|ADD_FAILD Invalid input data ");

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }


      $enterprise_number= $request->enterprise_number;

      $customer= Customers::where('enterprise_number',$enterprise_number)->whereIn('blocked',[0,1])->first();


      if(!$customer)
      {
        return $this->ApiReturn([],true, "Enterprise not active or not found",400);

      }



      if(config("server.backup_site"))
      {
        $customerBackup =CustomersBackup::where('enterprise_number', $enterprise_number)->whereIn("blocked",[0,1])->first();

        if(!$customerBackup)
        {

          return $this->ApiReturn(['enterprise_number' => 'Enterprise number not found on backup site'], false, 'The given data was invalid', 422);
        }

      }

      if (TosServices::where('cus_id', $customer->id)->where('service_key', $postData['service_key'])->exists()) {
        $textActiveService = $request->active == 1 ? "Kích hoạt" : "Hủy";
        $this->SetActivity($postData, "services_apps_linked", 0, 0, config("sbc.action.added_service_code"), $textActiveService . " dịch vụ GTGT mã: " . $request->service_key, $enterprise_number, null);

        $res = TosServices::where('cus_id', $customer->id)->where('service_key', $postData['service_key'])->update($postData);
      } else {
        $this->SetActivity($postData, "services_apps_linked", 0, 0, config("sbc.action.added_service_code"), "Thêm mới dịch vụ GTGT mã: " . $request->service_key, $enterprise_number, null);

        $res = TosServices::create($postData);
      }



      // Backup

      if(config('server.backup_site'))
      {


        $postData['cus_id']=$customerBackup->id;
        if (TosServicesBackup::where('cus_id', $customerBackup->id)->where('service_key', $postData['service_key'])->exists()) {

          $res = TosServicesBackup::where('cus_id', $customerBackup->id)->where('service_key', $postData['service_key'])->update($postData);
        } else {

          $res = TosServicesBackup::create($postData);
        }


      }

      // Backup

      $logDuration=  round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($request->all())."|ADD_TOS_SERVICE|".$logDuration."|success");


      return $this->ApiReturn(['data'=>$res],true, null, 200);


    }


    public function postTosService(Request $request)
    {

      // DONE BACKUP
      $user = $request->user;
      $startTime = round(microtime(true) * 1000);
      $tosProductCode= $request->tos_product_code;
      $enterprise= $request->enterprise_number;
      $tosUse= $request->use_tos;
      $validData = $request->only('enterprise_number','tos_product_code','use_tos');
      $validator = Validator::make($validData, [
        'enterprise_number' => 'required|alpha_dash|max:25|exists:customers,enterprise_number|exists:service_subcriber,enterprise_number',
        'use_tos'=>'required|in:0,1',
        'tos_product_code'=>'required'
      ]);
      if ($validator->fails()) {
        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }


      Log::info("START ADD_TOS_SERVICE TO:" . $request->enterprise_number . " BY UID:" . $user->id);



      $resCustomerId =   Customers::
         where('enterprise_number', $request->enterprise_number)
        ->where('blocked',0)
        ->first();
      if(!$resCustomerId)
      {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($validator) . "|ADD_CUSTOMER|" . $logDuration . "|INVALID TOS_PRODUCT_CODE IN USED");

        return $this->ApiReturn(['enterprise_number' => ["enterprise_number expired or not found"]], false, 'The given data was invalid', 422);
      }



      if(config("server.backup_site"))
      {
        $resCustomerIdBackup =CustomersBackup::where('enterprise_number', $request->enterprise_number)->whereIn("blocked",[0,1])->first();

        if(!$resCustomerIdBackup)
        {

          return $this->ApiReturn(['enterprise_number' => 'Enterprise number not found on backup site'], false, 'The given data was invalid', 422);
        }

      }




      // TODO VALIDATE TOS
      if ($tosProductCode) {
        if (strpos($tosProductCode, ',') !== false) {
          $listUseTosCode = explode(',', $tosProductCode);
          $listUseTos = explode(',', $tosUse);

          if(count($listUseTosCode) != count($listUseTos))
          {
            $logDuration = round(microtime(true) * 1000) - $startTime;
            Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($listUseTosCode) . "|ADD_CUSTOMER|" . $logDuration . "|INVALID TOS_PRODUCT_CODE IN USED");

            return $this->ApiReturn(['tos_product_code' => ["tos_product_code invalid or inactive"], 'use_tos'=>['use_tos not equal product code']], false, 'The given data was invalid', 422);
          }


          $inValidCode = DB::table('service_config')
            ->whereIn('product_code', $listUseTosCode)
            ->where('status', 0)->count();

          if ($inValidCode != count($listUseTosCode)) {
            $logDuration = round(microtime(true) * 1000) - $startTime;
            Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($listUseTosCode) . "|ADD_CUSTOMER|" . $logDuration . "|INVALID TOS_PRODUCT_CODE IN USED");

            return $this->ApiReturn(['tos_product_code' => ["tos_product_code invalid or inactive1"]], false, 'The given data was invalid', 422);
          }
        }
        else
        {

          $tosUse=$tosUse?$tosUse:0;

          $inValidCode = DB::table('service_config')
            ->where('product_code', $tosProductCode)
            ->where('status', 0)->first();

          if (!$inValidCode) {
            $logDuration = round(microtime(true) * 1000) - $startTime;
            Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($tosProductCode) . "|ADD_CUSTOMER|" . $logDuration . "|INVALID TOS_PRODUCT_CODE IN USED");

            return $this->ApiReturn(['tos_product_code' => ["tos_product_code invalid or inactive"]], false, 'The given data was invalid', 422);
          }
        }
      }
      // TODO TOS CHECK



      /** Thêm bản ghi tos  */
      if($tosProductCode)
      {


        if(isset($listUseTosCode)&& count($listUseTosCode)>0)
        {


          foreach ($listUseTosCode as $key => $value)
          {




            $postData=['cus_id'=>$resCustomerId->id, 'service_key'=>$value,'enterprise_number'=>$enterprise,'active'=>$listUseTos[$key]];


            if(TosServices::where('enterprise_number',$enterprise)->where('service_key', $value)->exists())
            {
              $resTosService=   TosServices::where('enterprise_number',$enterprise)->where('service_key', $value)->update($postData);
            }
            else
            {
              $resTosService=  TosServices::create($postData);
            }


            //            Backup

            if(config("server.backup_site"))
            {
              $postDataBk=['cus_id'=>$resCustomerIdBackup->id, 'service_key'=>$value,'enterprise_number'=>$enterprise,'active'=>$listUseTos[$key]];


              if(TosServicesBackup::where('enterprise_number',$enterprise)->where('service_key', $value)->exists())
              {
                $resTosService=   TosServicesBackup::where('enterprise_number',$enterprise)->where('service_key', $value)->update($postDataBk);
              }
              else
              {
                $resTosService=  TosServicesBackup::create($postDataBk);
              }


            }

            //            Backup

          }
        }
        else
        {
          $postData=['cus_id'=>$resCustomerId->id, 'service_key'=>$tosProductCode,'enterprise_number'=>$enterprise,'active'=>$tosUse];
          if(TosServices::where('enterprise_number',$enterprise)->where('service_key', $tosProductCode)->exists())
          {
            $resTosService=   TosServices::where('enterprise_number',$enterprise)->where('service_key', $tosProductCode)->update($postData);
          }
          else
          {
            $resTosService=  TosServices::create($postData);
          }

          //            Backup

          if(config("server.backup_site"))
          {

            $postDataBk=['cus_id'=>$resCustomerIdBackup->id, 'service_key'=>$tosProductCode,'enterprise_number'=>$enterprise,'active'=>$tosUse];
            if(TosServicesBackup::where('enterprise_number',$enterprise)->where('service_key', $tosProductCode)->exists())
            {
              $resTosService=   TosServicesBackup::where('enterprise_number',$enterprise)->where('service_key', $tosProductCode)->update($postDataBk);
            }
            else
            {
              $resTosService=  TosServicesBackup::create($postDataBk);
            }

          }

        }
      }

      return $this->ApiReturn([], true, null, 200);

    }


    public function changePauseState(Request $request)
    {
      $user = $request->user;
      $startTime = round(microtime(true) * 1000);

      if (!$this->checkEntity($user->id, "CHANGE_CUSTOMER_STATUS")) {
        Log::info($user->email . '  TRY TO GET V1Customer.changePauseState WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


      $validatedData = Validator::make($request->all(),[
        'action' => 'required|in:0,1',
        'direction' => 'required|in:11,12',
        'enterprise_number'=>'required|alpha_dash|max:25|exists:customers,enterprise_number',
        'reason'=>'nullable|unicode_valid|max:500',

      ]);



      if ($validatedData->fails()) {

        $logDuration= round(microtime(true) * 1000)-$startTime;
        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($request->all())."|CHANGE_CUSTOMER_PAUSE_STATE|".$logDuration."|CHANGE_FAIL INVALID DATA");

        return $this->ApiReturn($validatedData->errors(), false, 'The given data was invalid', 422);
      }



      $action= $request->action;
      $direction=$request->direction;
      $enterprise_number= $request->enterprise_number;

      $customer= Customers::where('enterprise_number',$enterprise_number)->whereIn('blocked',[0,1])->first();




      if(!$customer)
      {
        return $this->ApiReturn([],false, "Enterprise not active or not found",404);
      }

      $RUNONBACKUP= false;
      $listHotline = Hotlines::where("cus_id", $customer->id)->whereIn('status', [0, 1])->get();
      if($customer->server_profile  != config("server.server_profile"))
      {
        $customer= CustomersBackup::where('enterprise_number',$enterprise_number)->whereIn('blocked',[0,1])->first();

        $RUNONBACKUP= true;
        if(!$customer)
        {
          return $this->ApiReturn([],false, "Enterprise not active or not found on Backup server",404);
        }


      }


      $currentStatus= $customer->blocked;
      $currentPauseState= $customer->pause_state;


      $newState= $request->direction;
      $newStatus= $request->action;


      $resultState=null;
      $resultStatus= null;


      $inDirection= null; // Chiều gọi vào
      $inDirectionValue= null; // Giá trị trạng thái mới
      $outDirection= null; // Chiều gọi ra
      $outDirectionValue= null; // Giá trị trạng thái mới



      switch ($currentStatus) {
        case 1:
          Log::info("CUSTOMER_BLOCKED");
          switch ($currentPauseState) {
            case 10:
              Log::info("Đang chặn 2 chiều 1/10");
              if ($newStatus == 1) {
                // Chặn 1 trong 2 chiều/ không thực hiện gì cả
                Log::info("Đang chặn 2 chiều gửi lệnh chặn thêm sẽ không xử lý gì cả");
              } else {
                Log::info('Yêu cầu mở: trạng thái mới ' . $newStatus . ' state mới.' . $newState);
                if ($newState == 11) {

                  Log::info('Yêu cầu mở chiều gọi ra cho khách đang chặn 2 chiều ==> Kết quả chặn gọi vào');

                  $sbcDirection=1;

                  $resultState = 12;
                  $resultStatus = 1;

                  $outDirection= 1;
                  $outDirectionValue= 0;
                  $inDirection=2 ;
                  $inDirectionValue= 1;




                } else {
                  Log::info('Yêu cầu mở chiều gọi vào cho khách đang chặn 2 chiều ==> Kết quả chặn gọi ra');
                  $sbcDirection=2;
                  $resultState = 11;
                  $resultStatus = 1;

                  $outDirection= 1;
                  $outDirectionValue= 1;
                  $inDirection= 2;
                  $inDirectionValue= 0;



                }
              }
              break;
            case 11:
              Log::info("Khách đang bị chặn chiều gọi ra ");

              if ($newStatus == 1) {
                Log::info("Yêu cầu chặn  ");
                if ($newState == 11) {
                  Log::info("Khách đã bị chặn chiều gọi ra, không thực hiện gì cả");
                } else {
                  Log::info('Yêu cầu Chặn chiều gọi vào cho khách đang bị chặn gọi ra==>Kết quả Chặn 2 chiều');
                  $sbcDirection=2;
                  $resultState = 10;
                  $resultStatus = 1;

                  $outDirection= 1;
                  $outDirectionValue= 1;
                  $inDirection= 2;
                  $inDirectionValue= 1;



                }
              } else {
                Log::info('Yêu cầu mở  ' . $newStatus . ' NEW STATE:.' . $newState);
                if ($newState == 11) {
                  Log::info("Yêu cầu mở chiều gọi ra cho khách đang bị chặn chiều gọi ra  ==> kết quả mở 2 chièu");
                  $resultState = 10;
                  $resultStatus = 0;
                  $sbcDirection=1;


                  $outDirection= 1;
                  $outDirectionValue=0;
                  $inDirection= 2;
                  $inDirectionValue= 0;


                } else {
                  Log::info("Yêu cầu mở chiều gọi vào cho khách đang bị chặn chiều gọi ra, Khách đang ở trạng thái này. nên không thực hiện gì cả");

                }
              }

              break;
            case  12:
              Log::info("CUSTOMER_BLOCK_CALL_IN 1:12");

              if ($newStatus == 1) {
                // Trạng thái mới là chặn
                if ($newState == 11) {
                  Log::info("yêu cầu chặn chiều gọi ra cho khách đang bị chặn gọi vào ==>  Kết quả là chặn 2 chiều");
                  $resultState = 10;
                  $resultStatus =1;
                  $sbcDirection=1;


                  $outDirection= 1;
                  $outDirectionValue= 1;
                  $inDirection= 2;
                  $inDirectionValue= 1;




                } else {
                  Log::info("yêu cầu chặn chiều gọi ra cho khách đang bị chặn gọi ra ==> Không làm gì");
                  // yêu cầu chặn chiều gọi vào  cho khahchs đang bị chặn gọi vào Kết quả là không làm gì

                }
              } else {
                // Mở ra
                if ($newState == 11) {

                  // Yêu cầu mở gọi ra cho khách đang bị chặn gọi vào
                  Log::info("Yêu cầu mở gọi vào cho khách đang bị chặn gọi vào==> Không làm gì giữ nguyên kết quả ");
                } else {
                  // Yêu cầu mở gọi vào cho khách đang chặn gọi vào
                  Log::info("Yêu cầu mở gọi vào cho khách đang chặn gọi vào ==> Kết quả là Mở 2 chiều");
                  $resultStatus = 0;
                  $resultState = 10;
                  $sbcDirection=2;

                  $outDirection= 1;
                  $outDirectionValue= 0;
                  $inDirection= 2;
                  $inDirectionValue= 0;




                }
              }
              break;
          }
          break;
        case "0":
          Log::info("Khách đang mở 2 chiều");
          if ($newStatus == 1) {
            Log::info("Yêu cầu chặn");
            if ($newState == 11) {
              Log::info("Yêu cầu chặn chiều gọi ra cho khách đang mở 2 chiều => Chặn gọi ra");
              $resultState = 11;
              $resultStatus = 1;
              $sbcDirection=1;

              $outDirection= 1;
              $outDirectionValue= 1;
              $inDirection= 2;
              $inDirectionValue= 0;

            } else {

              Log::info("Yêu cầu chặn chiều gọi vào cho khách đang mở 2 chiều => Chặn gọi vào");


              $resultState = 12;
              $resultStatus = 1;
              $sbcDirection=2;

              $outDirection= 1;
              $outDirectionValue= 0;
              $inDirection= 2;
              $inDirectionValue= 1;


            }
          } else {

                    Log::info("Đang mở rồi, Gửi lệnh mở sẽ không giải quyết gì ");
          }

          break;
      }



      Log::info("Savel SBC: new status". $resultStatus);
      Log::info("State". $resultState);

      $listHotlineOk=[];

      /************************************************* UPDATE START  ------------------ **********/
      $numway= request('num_way',1);
      $reason= request('reason',"");
      foreach ($listHotline as $line) {
        if ($action == 0) {
          if ($reason == config('hotline.khyc')) {
            $statuslogDelete = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'));
            if ($numway == 1) {
              $statuslogDelete->where('pause_state', $direction);
            }

            $statuslogDelete->delete();

            $rejectUpdateHotline = HotlineStatusLog::where('hotline_id', $line->id)->where('pause_state', $direction)->where('reason', '!=', $reason)->first();
          } else {
            $statuslogDelete = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', '!=', config('hotline.khyc'));
            if ($numway == 1) {
              $statuslogDelete->where('pause_state', $direction);
            }

            $statuslogDelete->delete();
            $rejectUpdateHotline = HotlineStatusLog::where('hotline_id', $line->id)->where('pause_state', $direction)->where('reason', config('hotline.khyc'))->first();
          }

          if (!$rejectUpdateHotline) {
            array_push($listHotlineOk, ['id' => $line->id, 'number' => $line->hotline_number]);
          }
        } else {
          if ($reason == config('hotline.khyc')) {

            $lstCurrent = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'));
            if($numway==1)
            {
              $lstCurrent->where('pause_state', $direction);

            }
            $checkHotlineStatusLog= $lstCurrent->get();
          } else {

            $lstCurrent = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', '!=',config('hotline.khyc'));
            if($numway==1)
            {
              $lstCurrent->where('pause_state', $direction);

            }
            $checkHotlineStatusLog= $lstCurrent->get();

          }

          if (count($checkHotlineStatusLog)>0) {
            foreach ($checkHotlineStatusLog as $log)
            {
              HotlineStatusLog::where('id', $log->id)->update(["updated_at" => date("Y-m-d H:i:s"), 'reason' => $reason]);
            }

          }
          else
          {

            if($numway==1)
            {
              Log::info("Check in here numway 1 ");
              $checkHotlineStatusLog = new HotlineStatusLog();
              $checkHotlineStatusLog->hotline_id = $line->id;
              $checkHotlineStatusLog->pause_state = $direction;
              $checkHotlineStatusLog->cus_id = $customer->id;
              $checkHotlineStatusLog->reason = $reason;
              $checkHotlineStatusLog->enterprise_number = $enterprise_number;
              $checkHotlineStatusLog->user_id = $user->id;
              $checkHotlineStatusLog->hotline_number = $line->hotline_number;
              $checkHotlineStatusLog->save();

            }
            else
            {
              Log::info("Check in here numway 2 ");
              $checkHotlineStatusLog = new HotlineStatusLog();
              $checkHotlineStatusLog->hotline_id = $line->id;
              $checkHotlineStatusLog->pause_state = 11;
              $checkHotlineStatusLog->cus_id = $customer->id;
              $checkHotlineStatusLog->reason = $reason;
              $checkHotlineStatusLog->enterprise_number = $enterprise_number;
              $checkHotlineStatusLog->user_id = $user->id;
              $checkHotlineStatusLog->hotline_number = $line->hotline_number;
              $checkHotlineStatusLog->save();

              $checkHotlineStatusLog = new HotlineStatusLog();
              $checkHotlineStatusLog->hotline_id = $line->id;
              $checkHotlineStatusLog->pause_state = 12;
              $checkHotlineStatusLog->cus_id = $customer->id;
              $checkHotlineStatusLog->reason = $reason;
              $checkHotlineStatusLog->enterprise_number = $enterprise_number;
              $checkHotlineStatusLog->user_id = $user->id;
              $checkHotlineStatusLog->hotline_number = $line->hotline_number;
              $checkHotlineStatusLog->save();

            }



          }

          array_push($listHotlineOk, ['id' => $line->id, 'number' => $line->hotline_number]);

        }
      }

      /************************************************* UPDATE END ------------------ **********/




      if(isset($resultStatus) && $resultStatus> -1 && $resultState)
      {
        $customer->blocked= $resultStatus;
        $customer->pause_state= $resultState;

        $customer->save();


        $CDRAction= $sbcDirection==1?config("sbc.action_cdr.pause_call_out_cdr"):config("sbc.action_cdr.pause_call_in_cdr");
        $CDR= $request->enterprise_number."|$CDRAction|".date("YmdHis");
        $logAction= $sbcDirection==1?config("sbc.action.pause_call_out"):config("sbc.action.pause_call_in");

        $this->CDRActivity($customer->server_profile,$CDR, $request->enterprise_number,"PAUSE_STATE_CUSTOMER");


          Log::info("Lưu Hotline");
          $listHotline= Hotlines::where("cus_id", $customer->id) ->whereIn('status',[0,1])->get();

          if(count($listHotline)>0)
          {

            foreach ($listHotline as $line)
            {

              Hotlines::where('id', $line->id)->update(['status'=>$resultStatus, 'pause_state'=>$resultState]);
              $this->SetActivity($request->all(),'hot_line_config', $customer->id, 0,$logAction,   ($newStatus==1?"Chặn":"Mở")."1 chiều hotline khách hàng".$customer->companyname, $enterprise_number,$line->hotline_number);



            }

            $CallerGroup = SBCCallGroup::where('cus_id', $customer->id)->update(['status'=>$outDirectionValue]);


            Log::info("Lưu giá trị SBC");
            SBCRouting::where('i_customer', $customer->id)->where('direction', $outDirection)->update(["status" => $outDirectionValue]);
            SBCRouting::where('i_customer', $customer->id)->where('direction', $inDirection)->update(["status" => $inDirectionValue]);

          }

          $logAction= $sbcDirection==1?config("sbc.action.pause_call_out"):config("sbc.action.pause_call_in");

          $this->SetActivity($request->all(),'customers', $customer->id, 0,$logAction, ($newStatus==1?"Chặn":"Mở")." 1 chiều khách hàng ".$customer->companyname, $enterprise_number, null);
          Log::info("Direction: new status". $sbcDirection);



      }

      return    response()->json(["status"=>true],200);


    }

    public function postRechargeCustomer(Request $request)
    {

      $user = $request->user;
      $startTime = round(microtime(true) * 1000);

      if (!$this->checkEntity($user->id, "RE_CHARGE_MANUAL")) {
        Log::info($user->email . '  TRY TO GET V1Customer.postRechargeCustomer WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validatedData = $request->validate([
            'enterprise_number'=>'required|alpha_dash|max:25|exists:customers,enterprise_number'

      ]);


      $enterprise_number= $request->enterprise_number;

      $customer= Customers::where('enterprise_number',$enterprise_number)->whereIn('blocked',[0,1])->first();


      if(!$customer)
      {
        return $this->ApiReturn([],true, "Enterprise not active or not found",400);

      }
      $findLogs=ChargeLog::where("cus_id",$customer->id)->where("charge_time",">=",date("Y-m-01 00:00:00"))
        ->where("charge_status",0)->whereIn("charge_result",["409","401","404"])->get();
      if(count($findLogs)>0)
      {

        $findLogs2=ChargeLog::where("cus_id",$customer->id)->where("charge_time",">=",date("Y-m-01 00:00:00"))
          ->where("charge_status",0)->whereIn("charge_result",["409","401","404"])
          ->update(['retry_after'=>date("Y-m-d H:i:s"), 'retry_times'=>0]);

        $this->SetActivity($request->all(),'charge_log',0, 0,config('sbc.action.recharge'), "Charge bù cước khách hàng ".$customer->companyname, $enterprise_number, null);
        return $this->ApiReturn([],true, null,200);
      }

      return $this->ApiReturn([],false,"Not found error charge log",404);


    }

  public function getCustomerDetail($id, Request $request)
  {

    $user= $request->user;
    $validator = Validator::make(['enterprise_number'=>$id], [
      'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',


    ]);
    if ($validator->fails()) {
      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }

    Log::info($user->email."|GET_CUSTOMER_DETAIL|CUS_ID:".$id."|FROM:".$request->ip());
    $customer= Customers::where("enterprise_number",$id)->select("*")->first();
    if(!$customer)
    {
      return $this->ApiReturn(['id'=>['Not found customer enterprise number']], false, 'The given data was invalid', 422);
    }

    return $this->ApiReturn($customer, true, null, 200);


  }

  public function postUpdateCfu(Request $request)
  {

    $user= $request->user;



    if (!$this->checkEntity($user->id, "UPDATE_CFU_FLAG")) {
      Log::info($user->email . '  TRY TO Update V1CustomerController.postUpdateCfu WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
    }


    $validator = Validator::make($request->all(), [
      'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
      'cfu'=>'required|in:0,1'


    ]);
    if ($validator->fails()) {
      return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
    }


    $BACKUP_STATE = $request->single_mode ? false : config("server.backup_site");



    $enterpriseNumber= request("enterprise_number");
    $cfu= request("cfu");

    $customer = Customers::where("enterprise_number", $enterpriseNumber)->whereIn('blocked',[0,1])->first();
    if (!$customer) {
      return $this->ApiReturn(['enterprise_number' => ['Not found customer enterprise number']], false, 'The given data was invalid', 422);
    }

    if ($BACKUP_STATE) {
      $customerBackup = CustomersBackup::where("enterprise_number", $enterpriseNumber)->whereIn('blocked',[0,1])->first();
      if (!$customerBackup) {
        return $this->ApiReturn(['enterprise_number' => ['Not found customer enterprise number on backup']], false, 'The given data was invalid', 422);
      }
    }



    DB::beginTransaction();
    if($BACKUP_STATE)
    {
      DB::connection("db2")->beginTransaction();
    }

    try{

      $customer->cfu= $cfu;
      $customer->save();
      Hotlines::where('cus_id', $customer->id)->whereIn('status',[0,1])->update(['cfu'=>$cfu]);

      if($BACKUP_STATE)
      {
        HotlinesBackup::where('cus_id', $customerBackup->id)->whereIn('status',[0,1])->update(['cfu'=>$cfu]);
        $customerBackup->cfu= $cfu;
        $customerBackup->save();
      }



      DB::commit();
      if($BACKUP_STATE)
      {
        DB::connection("db2")->commit();
      }
    }
    catch (\Exception $exception)
    {
      Log::info($exception->getTraceAsString());
      DB::rollback();
      if($BACKUP_STATE)
      {
        DB::connection("db2")->rollback();
      }

      return $this->ApiReturn([],false,'Internal server error', 500);

    }

    return $this->ApiReturn([],true,null, 200);


  }

}










