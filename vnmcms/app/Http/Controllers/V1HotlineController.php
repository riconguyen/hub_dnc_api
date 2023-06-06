<?php

  namespace App\Http\Controllers;

  use App\Customers;
  use App\CustomersBackup;
  use App\Hotlines;
  use App\HotlinesBackup;
  use App\HotlineStatusLog;
  use App\HotlineStatusLogBackup;
  use App\SBCAcl;
  use App\SBCCallGroup;
  use App\SBCRouting;
  use App\SBCRoutingBackup;
  use App\ServiceConfig;
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Log;
  use Validator;

  class V1HotlineController extends Controller
  {
    //

    public function getCustomerHotlines(Request $request)  // DOC 4.1
    {
      $user = $request->user;

      if (!$this->checkEntity($user->id, "VIEW_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.getCustomerHotlines WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validData = $request->only('enterprise_number');
      $validator = Validator::make($validData, ['enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number']);
      if ($validator->fails()) {
        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }
      $resHotline = DB::table('hot_line_config as A')->where('A.enterprise_number', $request->enterprise_number)->whereIn('A.status', [0, 1])->join('customers as B', 'A.cus_id', '=', 'B.id')->select('A.hotline_number', 'A.enterprise_number', 'A.updated_at', 'A.created_at', 'A.status', 'B.companyname', 'B.cus_name')->get();
      return $this->ApiReturn($resHotline, true, null, 200);
    }

    public function addCustomerHotlines(Request $request) // DOC 4.2
    {
      //      return ['a'=>$request->api_source];

      $BACKUPSTATE = $request->single_mode == 1 ? false : config("server.backup_site");
      $API_STATE = $request->api_source ? "API|" : "WEB|";
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;

      if (!$this->checkEntity($user->id, "ADD_HOTLINE")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.addCustomerHotlines WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validData = $request->only('enterprise_number', 'hotline_numbers', 'profile_id_backup');
      $validator = Validator::make($validData, ['enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number', 'profile_id_backup' => 'nullable|in:2,3,4', 'hotline_numbers' => 'required|number_dash|max:1500']);
      if ($validator->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_CUSTOMER_HOTLINE|" . $logDuration . "|ADD_CUSTOMER_HOTLINE_FAIL Invalid data");

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }

      if (strpos($request->hotline_numbers, ',') !== false) {
        $hotlineNumbers = explode(',', $request->hotline_numbers);
      } else {
        $hotlineNumbers = [$request->hotline_numbers];
      }

      // Validate if is Hotline Number

      $lstErrors = [];

      foreach ($hotlineNumbers as $number) {
        $re = '/^[0-1][0-9]{7,11}$/m';

        if (preg_match($re, $number, $matches, PREG_OFFSET_CAPTURE, 0)) {
        } else {
          array_push($lstErrors, $number);
        }
      }
      if (count($lstErrors) > 0) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_CUSTOMER_HOTLINE|" . $logDuration . "|ADD_CUSTOMER_HOTLINE_FAIL Hotline invalid format|" . implode(",", $lstErrors));

        return $this->ApiReturn(["hotline_numbers" => ["Hotline numbers is invalid"]], false, 'The given data was invalid: ' . implode(",", $lstErrors), 422);
      }

      $lstInUseHotline = Hotlines::whereIn('hotline_number', $hotlineNumbers)->whereIn('status', [0, 1])->get();
      if (count($lstInUseHotline) > 0) {
        $linesInUsed = [];
        foreach ($lstInUseHotline as $line) {
          array_push($linesInUsed, $line->hotline_number);
        }

        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_CUSTOMER_HOTLINE|" . $logDuration . "|ADD_CUSTOMER_HOTLINE_FAIL Hotlines in used|" . implode(",", $linesInUsed));

        return $this->ApiReturn(["hotline_numbers" => ["Hotline numbers is uses " . implode(",", $linesInUsed)]], false, 'The given data was invalid', 422);
      }

      $enterprise = $request->enterprise_number;

      $customer = Customers::where("enterprise_number", $enterprise)->whereIn('blocked', [0, 1])->first();
      $vendorData = DB::table('sbc.vendors')->where('i_vendor', $request->vendor_id ? $request->vendor_id : 1)->first();
      $service= ServiceConfig::where('id',$customer->service_id)->first();
      $isBackup = false;
      if (!$customer) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_CUSTOMER_HOTLINE|" . $logDuration . "|ADD_CUSTOMER_HOTLINE_FAIL|Not found active enterprise number");

        return $this->ApiReturn([], false, "Not found active enterprise number " . $enterprise, 404);
      }

      if ($customer->server_profile != config("server.server_profile")) {
        $isBackup = true;
      }


      $use_brand_name = false;
      if ($customer) {
        $use_brand_name = ServiceConfig::where("id", $customer->service_id)->where("product_code", "like", '%VB%')->exists();
      }

      $sip = (object)[];

      DB::beginTransaction();

      $initHotlineInitCharge = 0;

      try {
        // NORMALL CASE =============================================================================================================================================

        foreach ($hotlineNumbers as $line) {
          $data = array('cus_id' => $customer->id, 'init_charge' => $initHotlineInitCharge,
            'enterprise_number' => $request->enterprise_number,
            'hotline_number' => $line,
            'status' => $customer->blocked,
            'use_brand_name' => $use_brand_name,
            'ocs_charge'=>$service?$service->ocs_charge:0

          );

          if ($isBackup) {
            $data['status'] = 1;
            $data['pause_state'] = 11;
          }
          $hotlineId = Hotlines::insertGetId($data);

          $CDR_TEXT = $enterprise . "|" . config("sbc.CDR.CHANGE") . "|" . date("YmdHis") . "|" . $line;
          if (!$isBackup) {
            $this->CDRActivity($customer->server_profile, $CDR_TEXT, $enterprise, $API_STATE . "ADD_HOTLINE");
          }

          $this->SetActivity($data, 'hot_line_config', $hotlineId, 0, config("sbc.action.add_hotline"), "Tạo mới hotlines " . $line, $request->enterprise_number, $line);
          $sip->hotline_id = $hotlineId;
          $sip->cus_id = $customer->id;
          $sip->hotline = $line;
          $sip->ip_auth = $customer->ip_auth;
          $sip->ip_auth_backup = $customer->ip_auth_backup;
          $sip->ip_proxy_backup = $customer->ip_proxy_backup;
          $sip->vendor = $vendorData;
          $sip->enterprise_number = $customer->enterprise_number;
          $sip->ip_proxy = $customer->ip_proxy;
          $sip->description = $customer->enterprise_number;
          $sip->destination = $customer->destination;
          $sip->telco_destination = $customer->telco_destination;
          $sip->profile_id_backup = $request->profile_id_backup ? $request->profile_id_backup : config('sbc.profile_id_backup');
          $sip->isRunOnBackup = $isBackup;
          $sip->status = $data['status'];

          $sipOk = $this->addSipRouting($sip, false);
        }


        DB::commit();


        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_CUSTOMER_HOTLINE|" . $logDuration . "|ADD_CUSTOMER_HOTLINE_SUCCESS");

        return $this->ApiReturn(null, true, null, 200);
      } catch (\Exception $e) {
        DB::rollBack();

        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ADD_CUSTOMER_HOTLINE|" . $logDuration . "|ADD_CUSTOMER_HOTLINE_FAIL Error 500");
        Log::info(json_encode($e));

        return $this->ApiReturn($e->getTraceAsString(), false, 'Error transaction', 500);
      }
    }

    public function getHotlineConfig(Request $request) {
      $user = $request->user;
      if (!$this->checkEntity($user->id, "VIEW_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.getHotlineConfig WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validData = $request->only('hotline_number');
      $validator = Validator::make($validData, ['hotline_number' => 'required|phone_valid|max:250|exists:hot_line_config,hotline_number']);
      if ($validator->fails()) {
        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }
      $hotlineInfo = DB::table('hot_line_config as a')->where('hotline_number', $request->hotline_number)->whereIn('a.status', [0, 1])
          ->leftJoin('sbc.routing as b', 'a.hotline_number', '=', 'b.callee')
          ->leftJoin('sbc.routing as c', 'a.hotline_number', '=', 'c.caller')
          ->leftJoin('sbc.acl as d', 'c.i_acl', '=', 'd.i_acl')
          ->leftJoin('sbc.acl as e', 'c.i_acl_backup', '=', 'e.i_acl')
          ->select('enterprise_number', 'a.status', 'updated_at', 'a.hotline_number', 'e.ip_auth as ip_auth_backup',
              'b.destination',
              'c.destination as telco_destination',
              'b.i_sip_profile as profile',
              'd.ip_auth', 'd.ip_proxy')->first();

      if ($hotlineInfo) {
        return $this->ApiReturn($hotlineInfo, true, null, 200);
      } else {
        return $this->ApiReturn(['hotline_number' => ['The hotline number is invalid']], false, 'The given data was invalid', 422);
      }
    }

    /**
     *
     */
    public function changeHotlineConfig(Request $request) {
      //BACKUP DONE
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;
      $BACKUP_STATE = $request->single_mode ? false : config("server.backup_site");
      if (!$this->checkEntity($user->id, "CONFIG_HOTLINE")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.changeHotlineConfig WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validData = $request->all();
      $validator = Validator::make($validData, ['hotline_number' => 'required|phone_valid|max:250|exists:hot_line_config,hotline_number', 'enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number', 'ip_auth' => 'nullable|ipv4|max:50', 'ip_auth_backup' => 'nullable|ipv4|max:50', 'ip_proxy_backup' => 'nullable|ipv4|max:50', 'ip_proxy' => 'nullable|ipv4|max:50', 'destination' => 'nullable|ipV4Port|max:100',]);
      if ($validator->fails()) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_CONFIG|" . $logDuration . "|CHANGE_HOTLINE_CONFIG_FAIL Invalid data input ");
        /** @var LOG $logDuration */

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }
      $enterprise_number = $request->enterprise_number;
      $lineNumber = $request->hotline_number;

      $customer = Customers::where("enterprise_number", $request->enterprise_number)->whereIn('blocked', [0, 1])->first();

      if (!$customer) {
        Log::info("NOT FOUND CUSTOMER ", $enterprise_number);
        return $this->ApiReturn([], false, 'Not found active enterprise_number', 422);
      }

      $hotLine = Hotlines::where('hotline_number', $request->hotline_number)->where('cus_id', $customer->id)->whereIn('status', [0, 1])->whereNotNull('sip_config')->first();

      if (!$hotLine) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_CONFIG|" . $logDuration . "|CHANGE_HOTLINE_CONFIG_FAIL Invalid enterprise number ");
        /** @var LOG $logDuration */

        return $this->ApiReturn(['hotline_number' => 'Hotline number not match with present enterprise number'], false, 'The given data was invalid', 422);
      }

      $this->SetActivity($request->all(), 'hot_line_config', $hotLine->id, 0, config("sbc.action.update_sip_config"), "Cập nhật cấu hình sip của hotline " . $lineNumber, $enterprise_number, $lineNumber);

      $ip_proxy_backup = request('ip_proxy_backup', null);
      $ip_auth_backup = request('ip_auth_backup', null);

      DB::beginTransaction();

      try {
        $routingCallee = SBCRouting::where('callee', $hotLine->hotline_number)->where('i_customer', $hotLine->cus_id)->first();
        if ($request->destination) {
          $routingCallee->destination = $request->destination;
        }
        $routingCallee->save();

        $routingCaller = SBCRouting::where('caller', $hotLine->hotline_number)->where('i_customer', $hotLine->cus_id)->first();
        $acl = SBCAcl::where('i_acl', $routingCaller->i_acl)->first();
        if ($acl) {
          if ($request->ip_auth) {
            $acl->ip_auth = $request->ip_auth; // Lưuu IP Auth
          }
          if ($request->ip_proxy) {
            $acl->ip_proxy = $request->ip_proxy;
          }
          $acl->save();
        }

        $telco_destination=request('telco_destination',null);
        if($telco_destination)
        {
            $routingCaller->destination= $telco_destination;
        }

        if ($ip_proxy_backup || $ip_auth_backup) {
          if ($routingCaller->i_acl_backup && $routingCaller->i_acl != $routingCaller->i_acl_backup) {
            $aclBackup = SBCAcl::where('i_acl', $routingCaller->i_acl_backup)->first();
          } else {
            $aclBackup = new SBCAcl();
            $aclBackup->block_regex_caller = $acl ? $acl->block_regex_caller : '';
            $aclBackup->block_regex_callee = $acl ? $acl->block_regex_callee : '^(00|\\\\+84|1900|1800).*';
            $aclBackup->allow_regex_caller = $acl ? $acl->allow_regex_caller : '';
            $aclBackup->allow_regex_callee = $acl ? $acl->allow_regex_callee : '^0[0-9]{8,11}$';
          }
          $aclBackup->ip_proxy = $ip_proxy_backup;
          $aclBackup->ip_auth = $ip_auth_backup ? $ip_auth_backup : $acl->ip_auth;
          $aclBackup->save();

          $routingCaller->i_acl_backup = $aclBackup->i_acl;


          $routingCaller->save();
        } else {
          $routingCaller->i_acl_backup = $routingCaller->i_acl;
          $routingCaller->save();
        }

        $hotLine->updated_at = date("Y-m-d H:i:s");
        $hotLine->save();



        DB::commit();
      } catch (\Exception $exception) {
        Log::info($exception->getTraceAsString());
        DB::rollback();

        return $this->ApiReturn(null, false, "Internal server error", 500);
      }

      /** @var LOG $logDuration */
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_CONFIG|" . $logDuration . "|CHANGE_HOTLINE_CONFIG_SUCCESS");
      /** @var LOG $logDuration */

      return $this->ApiReturn(null, true, null, 200);
    }

    public function changeHotlineStatus(Request $request) {
      $startTime = round(microtime(true) * 1000);
      $API_STATE = $request->api_source ? "API|" : "WEB|";

      $BACKUP_STATE = $request->single_mode ? false : config("server.backup_site");

      $user = $request->user;

      if (!$this->checkEntity($user->id, "CHANGE_HOTLINE_STATUS")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.changeHotlineStatus WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validData = $request->only('enterprise_number', 'hotline_number', 'status', 'reason');
      $validator = Validator::make($validData, ['enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number',
        'hotline_number' => 'required|phone_valid|max:250|exists:hot_line_config',
        'status' => 'required|in:0,1',
        'reason' => 'nullable|unicode_valid|max:500']);
      if ($validator->fails()) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_STATUS|" . $logDuration . "|CHANGE_HOTLINE_STATUS_FAIL Invalid input data");
        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }

      $hotLine = $request->hotline_number;
      $enterprise = $request->enterprise_number;

      $customer = Customers::where('enterprise_number', $enterprise)->whereIn('blocked', [0, 1])->first();
      if (!$customer) {
        Log::info("Not found customer: " . $enterprise);
        return $this->ApiReturn([], false, "Enterprise not active or not found", 404);
      }

      $isBackup = false;




      $reason = request('reason', "--");

      DB::beginTransaction();


      try {
        $line = Hotlines::where('hotline_number', $hotLine)->where('cus_id', $customer->id)->whereIn('status', [0, 1])->first();



        if ((!$line || $customer->blocked == 1) && !$isBackup ) {
          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_STATUS|" . $logDuration . "|CHANGE_HOTLINE_STATUS_FAIL Hotline not exists or customers on PAUSE ");
          return $this->ApiReturn(['hotline_number' => 'Hotline number not match with present enterprise number or deleted'], false, 'The given data was invalid', 422);
        }

        $newStatus = $request->status;
        $CDR_ACTION = $newStatus == 1 ? config("sbc.CDR.PAUSE") : config("sbc.CDR.ACTIVE");
        $CDR_TEXT = $enterprise . "|" . $CDR_ACTION . "|" . date("YmdHis") . "|" . $hotLine;
        $this->CDRActivity($customer->server_profile, $CDR_TEXT, $enterprise, $API_STATE . "CHANGE_STATUS");



          $saveStatusHotline = true;
          if ($newStatus == 0) {


            if ($reason == config('hotline.khyc')) {

              HotlineStatusLog::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'))->delete();


              $resLog = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', '!=', config('hotline.khyc'))->first();
              if ($resLog) {
                $saveStatusHotline = false;
              }
              // Mở bình thường
            } else {

              HotlineStatusLog::where('hotline_id', $line->id)->where('reason', '!=', config('hotline.khyc'))->delete();

              $resLog = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'))->first();

              if ($resLog) {
                $saveStatusHotline = false;
              }
            }


          } else { //

            Log::info("CHECK reason".$reason);
            Log::info("CHECK hotline.khyc".config('hotline.khyc'));


            if ($reason == config('hotline.khyc')) // Khóa theo KHYC
            {
              $res = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'))->get();

              if (count($res) > 0) {
                foreach ($res as $log) {
                  HotlineStatusLog::where('id', $log->id)->update(["updated_at" => date("Y-m-d H:i:s"), 'reason' => $reason]);
                }
              } else {
                HotlineStatusLog::insert(['hotline_id' => $line->id, 'cus_id' => $customer->id,
                  'hotline_number' => $line->hotline_number,
                  'enterprise_number' => $enterprise,
                  'reason' => $reason,
                  'pause_state'=>11,
                  'user_id' => $user->id]);

                HotlineStatusLog::insert(['hotline_id' => $line->id, 'cus_id' => $customer->id,
                  'hotline_number' => $line->hotline_number,
                  'enterprise_number' => $enterprise,
                  'reason' => $reason,
                  'pause_state'=>12,
                  'user_id' => $user->id]);


              }
            } else {
              $res = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', '!=', config('hotline.khyc'))->get();

              if (count($res) > 0) {
                foreach ($res as $log) {
                  HotlineStatusLog::where('id', $log->id)->update(["updated_at" => date("Y-m-d H:i:s"), 'reason' => $reason]);
                }
              } else {
                HotlineStatusLog::insert([
                  'hotline_id' => $line->id,
                  'cus_id' => $customer->id,
                  'hotline_number' => $line->hotline_number,
                  'enterprise_number' => $enterprise,
                  'user_id' => $user->id,
                  'pause_state'=>11,
                  'reason' => $reason,]);
                HotlineStatusLog::insert([
                  'hotline_id' => $line->id,
                  'cus_id' => $customer->id,
                  'hotline_number' => $line->hotline_number,
                  'enterprise_number' => $enterprise,
                  'user_id' => $user->id,
                  'pause_state'=>12,
                  'reason' => $reason,]);
              }
            }
          }

          if ($saveStatusHotline) {
            $textLogHotline = $newStatus == 1 ? "Chặn 2 chiều hotline " : "Mở 2 chiều hotline ";
            $this->SetActivity($request->all(), 'hot_line_config', $line->id, 0, config("sbc.action.pause_state_hotline"), $textLogHotline . $hotLine, $enterprise, $hotLine);

            $line->status = $newStatus;
            $line->updated_at = date("Y-m-d H:i:s");
            if ($newStatus == 0) {
              $line->pause_state = "10";
            }
            //          SBCRouting::where('caller', $request->hotline_number)->orWhere('callee', $request->hotline_number)->->update(['status' => $newStatus]);

            $res=    DB::update("update sbc.routing set status=?  where i_customer=? and (ifNUll(callee,'')= ? or ifNull(caller,'')=?) ", [$newStatus, $customer->id, $hotLine, $hotLine]);
            Log::info([$newStatus, $customer->id, $hotLine, $hotLine]);



            $line->save();

            $CallerGroup = SBCCallGroup::where('cus_id', $customer->id)->where('caller',$line->hotline_number)->update(['status'=>$newStatus]);

            /** @var LOG $logDuration */
            $logDuration = round(microtime(true) * 1000) - $startTime;
            Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_STATUS|" . $logDuration . "|CHANGE_HOTLINE_STATUS_SUCCESS");
          }



        DB::commit();

      } catch (\Exception $exception) {
        Log::info($exception->getTraceAsString());
        DB::rollback();


        return $this->ApiReturn(null, false, "Internal server error", 500);
      }

      return $this->ApiReturn(null, true, null, 200);
    }

    public function removeHotline(Request $request, ActivityController $activityController) {
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;

      $API_STATE = $request->api_source ? "API|" : "WEB|";

      $BACKUP_STATE = $request->single_mode ? false : config("server.backup_site");

      if (!$this->checkEntity($user->id, "REMOVE_HOTLINE")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.removeHotline WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validData = $request->only('enterprise_number', 'hotline_number');
      $validator = Validator::make($validData, ['enterprise_number' => 'required|alpha_dash|max:25|exists:customers,enterprise_number', 'hotline_number' => 'required|alpha_num|max:25|exists:hot_line_config|max:250',

      ]);
      if ($validator->fails()) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_HOTLINE|" . $logDuration . "|DELETE_HOTLINE_FAIL Invalid input data");
        /** @var LOG $logDuration */

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }
      $hotLine = DB::table('hot_line_config')->where('hotline_number', $request->hotline_number)->where('enterprise_number', $request->enterprise_number)->whereIn('status', [0, 1])->first();
      if (!$hotLine) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_HOTLINE|" . $logDuration . "|DELETE_HOTLINE_FAIL invalid");
        /** @var LOG $logDuration */

        return $this->ApiReturn(['hotline_number' => 'Hotline number not match with present enterprise number'], false, 'The given data was invalid', 422);
      }

      $line = $request->hotline_number;
      $enterprise = $request->enterprise_number;

      $customer = Customers::where('enterprise_number', $enterprise)->whereIn('blocked', [0, 1])->first();

      if (!$customer) {
        return $this->ApiReturn([], false, "Enterprise not active or not found", 404);
      }

      DB::beginTransaction();


      try {
        $this->SetActivity($request->all(), 'hot_line_config', $hotLine->id, 0, config("sbc.action.cancel_hotline"), "Hủy hotline " . $line, $enterprise, $line);

        Hotlines::where('hotline_number', $request->hotline_number)->whereIn('status', [0, 1])->update(['status' => 2, 'sip_config' => null, 'updated_at' => date("Y-m-d H:i:s")]);

        SBCRouting::where('caller', $request->hotline_number)->orWhere('callee', $request->hotline_number)->delete();
        SBCCallGroup::where('cus_id',$customer->id)->where('caller',$line)->delete();

        $CDR = $request->enterprise_number . "|2|" . date("YmdHis") . "|" . $request->hotline_number;
        $this->CDRActivity($customer->server_profile, $CDR, $request->enterprise_number, $API_STATE . "REMOVE_HOTLINE");

        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|DELETE_HOTLINE|" . $logDuration . "|DELETE_HOTLINE_SUCCESS");

        DB::commit();


      } catch (\Exception $exception) {
        Log::info(json_encode($exception));
        DB::rollback();


        return $this->ApiReturn([], false, "Error remove Hotline ", 500);
      }

      //        $activityController->AddActivity($validData, 'hot_line_config', $hotLine->id, 0, 'api/changeHotlineStatus');
      //        $activityController->AddActivity($validData, 'sbc.routing', $hotLine->id, 0, 'api/removeHotline');
      /** @var LOG $logDuration */

      /** @var LOG $logDuration */

      return $this->ApiReturn(null, true, null, 200);
    }

    public function changeHotlineProfile(Request $request) {
      //BACKUP DONE
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;

      if (!$this->checkEntity($user->id, "CHANGE_PROFILE_HOTLINE")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.changeHotlineProfile WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validData = $request->only('enterprise_number', 'hotline_number', 'profile');
      $validator = Validator::make($validData, ['enterprise_number' => 'required|alpha_dash|max:250|exists:customers,enterprise_number', 'hotline_number' => 'required|phone_valid|max:250|exists:hot_line_config', 'profile' => 'required|numeric|in:2,3,4',

      ]);
      if ($validator->fails()) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_FAIL Invalid input data");
        /** @var LOG $logDuration */

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }

      // Check in Enterprisse

      $cus = DB::table("customers")->where("enterprise_number", $request->enterprise_number)->whereIn("blocked", [0, 1])->first();

      if (!$cus) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_FAIL Customer has been deactivate or canceled ");
        /** @var LOG $logDuration */
        return $this->ApiReturn(["enterprise_number" => ['Error enterprise number']], false, 'Customer has been deactivate or canceled ', 422);
      }

      if (config("server.backup_site")) {
        $cusBackup = CustomersBackup::where("enterprise_number", $request->enterprise_number)->whereIn("blocked", [0, 1])->first();

        if (!$cusBackup) {
          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_FAIL Customer BACKUP has been deactivate or canceled ");
          /** @var LOG $logDuration */
          return $this->ApiReturn(["enterprise_number" => ['Error enterprise number']], false, 'BACKUP Customer has been deactivate or canceled ', 422);
        }
      }

      $routingUpdate = ["i_sip_profile" => $request->profile];

      $hotline = DB::table("hot_line_config")->where("hotline_number", $request->hotline_number)->where('enterprise_number', $request->enterprise_number)->whereIn('status', [0, 1])->first();

      if (!$hotline) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_FAIL Hotline  has been deactivate or canceled ");
        /** @var LOG $logDuration */

        return $this->ApiReturn(["hotine_number" => ['Error Hotline']], false, 'Hotline  has been deactivate or canceled ', 422);
      }
      // Update lên server hiện tại
      $this->SetActivity($routingUpdate, "sbc.routing", 0, 0, config("sbc.update_sip_config"), "Thay đổi cấu hình profile hotline thành " . $request->profile, $cus->enterprise_number, $request->hotline_number);

      DB::beginTransaction();
      try {
        DB::table('sbc.routing')->where('direction', 2)->where('i_customer', $cus->id)->where('callee', $request->hotline_number)->update($routingUpdate);

        DB::commit();
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_SUCCESS");
        /** @var LOG $logDuration */
      } catch (\Exception $exception) {
        DB::rollback();

        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_FAIL Error update data");
        /** @var LOG $logDuration */
        Log::info(json_encode($exception->getTraceAsString()));

        return $this->ApiReturn([], false, 'Error Internal ', 500);
      }

      if (config("server.backup_site")) {
        // UPDATE LEN SERVER BACKUP
        $hotline = DB::connection("db2")->table("hot_line_config")->where("hotline_number", $request->hotline_number)->where('enterprise_number', $request->enterprise_number)->whereIn('status', [0, 1])->first();

        if (!$hotline) {
          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_FAIL Hotline  has been deactivate or canceled ");
          /** @var LOG $logDuration */

          return $this->ApiReturn(["hotine_number" => ['Error Hotline']], false, 'Hotline  has been deactivate or canceled ', 422);
        }
        // Update lên server hiện tại
        $this->SetActivity($routingUpdate, "sbc.routing", 0, 0, config("sbc.update_sip_config"), "[BACKUP] Thay đổi cấu hình profile hotline thành " . $request->profile, $cus->enterprise_number, $request->hotline_number);

        DB::connection("db2")->beginTransaction();
        try {
          DB::connection("db2")->table('sbc.routing')->where('direction', 2)->where('i_customer', $cusBackup->id)->where('callee', $request->hotline_number)->update($routingUpdate);

          DB::connection("db2")->commit();
          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_SUCCESS");
          /** @var LOG $logDuration */
        } catch (\Exception $exception) {
          DB::connection("db2")->rollback();

          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_FAIL Error update data");
          /** @var LOG $logDuration */

          return $this->ApiReturn([], false, 'Error Internal Backup', 500);
        }
      }

      return $this->ApiReturn(null, true, null, 200);
    }

    public function changePauseStateHotline(Request $request) {
      $user = $request->user;
      $startTime = round(microtime(true) * 1000);

      $BACKUPSTATE = $request->single_mode == 1 ? false : config("server.backup_site");
      $API_STATE = $request->api_source ? "API|" : "WEB|";
      Log::info("Change pause state hotline" . $API_STATE);
      if (!$this->checkEntity($user->id, "CHANGE_HOTLINE_STATUS")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.changePauseStateHotline WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validatedData = Validator::make($request->all(), ['action' => 'required|in:0,1', 'direction' => 'required|in:11,12', 'hotline_number' => 'required|max:25|exists:hot_line_config,hotline_number', 'enterprise_number' => 'required|max:25|exists:customers,enterprise_number', 'reason' => 'nullable|unicode_valid|max:500']);

      if ($validatedData->fails()) {
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_ENTERPRISE_NUMBER|" . $logDuration . "|CHANGE_FAIL INVALID DATA");

        return $this->ApiReturn($validatedData->errors(), false, 'The given data was invalid', 422);
      }



      $action = $request->action;
      $direction = $request->direction;
      $hotlineNo = $request->hotline_number;
      $numway=request('num_way',1);
      $reason=request('reason');
      $enterprise_number = $request->enterprise_number;
      $listHotlineOk=[];


      $customer = Customers::where('enterprise_number', $enterprise_number)->whereIn('blocked', [0])->first();

      if (!$customer) {
        return $this->ApiReturn([], false, "Enterprise not active or not found", 404);
      }

      $RUNONBACKUP = false;

      if ($customer->server_profile != config("server.server_profile")) {
        Log::info("Customer run on backup server:" . $customer->server_profile);

        $customer = CustomersBackup::where('enterprise_number', $enterprise_number)->whereIn('blocked', [0])->first();

        if (!$customer) {
          return $this->ApiReturn([], false, "Enterprise not active on Backup or not found", 404);
        }
        $RUNONBACKUP = true;
      }

      if ($RUNONBACKUP) {
        $line = HotlinesBackup::where('hotline_number', $hotlineNo)->where("cus_id", $customer->id)->whereIn('status', [0, 1])->first();
      } else {
        $line = Hotlines::where('hotline_number', $hotlineNo)->where("cus_id", $customer->id)->whereIn('status', [0, 1])->first();
      }

      if (!$line) {
        return $this->ApiReturn([], false, "Hotline not active or not found", 404);
      }

      // UPdate KHYC


      if ($action == 0) {
        if ($reason == config('hotline.khyc')) {
          $resDelete = HotlineStatusLog::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'));
          if ($numway == 1) {
            $resDelete->where('pause_state', $direction);
          }

          $resDelete->delete();

          $rejectUpdateHotline = HotlineStatusLog::where('hotline_id', $line->id)->where('pause_state', $direction)->where('reason', '!=', $reason)->first();
        } else {

          $resDelete = HotlineStatusLog::where('hotline_id', $line->id)->where('reason','!=', config('hotline.khyc'));
          if ($numway == 1) {
            $resDelete->where('pause_state', $direction);
          }

          $resDelete->delete();


          $rejectUpdateHotline = HotlineStatusLog::where('hotline_id', $line->id)->where('pause_state', $direction)->where('reason', config('hotline.khyc'))->first();
        }

        if (!$rejectUpdateHotline) {
          array_push($listHotlineOk, ['id' => $line->id, 'number' => $line->hotline_number]);
        }
      } else {
        if ($reason == config('hotline.khyc')) {
          $lstLog= HotlineStatusLog::where('hotline_id', $line->id)->where('reason', config('hotline.khyc'));
          if($numway==1)
          {
            $lstLog->where('pause_state',$direction);
          }

          $checkHotlineStatusLog=$lstLog->get();
        } else {
          $lstLog= HotlineStatusLog::where('hotline_id', $line->id)->where('reason', '!=', config('hotline.khyc'));
          if($numway==1)
          {
            $lstLog->where('pause_state',$direction);
          }

          $checkHotlineStatusLog=$lstLog->get();
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


      // UPdate KHYC




      $newState = $request->direction;
      $newStatus = $request->action;
      $currentStatus = $line->status;
      $currentPauseState = $line->pause_state;

      $resultState = null;
      $resultStatus = null;
      $sbcDirection = null;

      $inDirection = null; // Chiều gọi vào
      $inDirectionValue = null; // Giá trị trạng thái mới
      $outDirection = null; // Chiều gọi ra
      $outDirectionValue = null; // Giá trị trạng thái mới

      Log::info("CHANGE_PAUSE_STATE_HOTLINE_START \n");

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

                  $sbcDirection = 1;

                  $resultState = 12;
                  $resultStatus = 1;

                  $outDirection = 1;
                  $outDirectionValue = 0;
                  $inDirection = 2;
                  $inDirectionValue = 1;
                } else {
                  Log::info('Yêu cầu mở chiều gọi vào cho khách đang chặn 2 chiều ==> Kết quả chặn gọi ra');
                  $sbcDirection = 2;
                  $resultState = 11;
                  $resultStatus = 1;

                  $outDirection = 1;
                  $outDirectionValue = 1;
                  $inDirection = 2;
                  $inDirectionValue = 0;
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
                  $sbcDirection = 2;
                  $resultState = 10;
                  $resultStatus = 1;

                  $outDirection = 1;
                  $outDirectionValue = 1;
                  $inDirection = 2;
                  $inDirectionValue = 1;
                }
              } else {
                Log::info('Yêu cầu mở  ' . $newStatus . ' NEW STATE:.' . $newState);
                if ($newState == 11) {
                  Log::info("Yêu cầu mở chiều gọi ra cho khách đang bị chặn chiều gọi ra  ==> kết quả mở 2 chièu");
                  $resultState = 10;
                  $resultStatus = 0;
                  $sbcDirection = 1;

                  $outDirection = 1;
                  $outDirectionValue = 0;
                  $inDirection = 2;
                  $inDirectionValue = 0;
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
                  $resultStatus = 1;
                  $sbcDirection = 1;
                  $outDirection = 1;
                  $outDirectionValue = 1;
                  $inDirection = 2;
                  $inDirectionValue = 1;
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
                  $sbcDirection = 2;

                  $outDirection = 1;
                  $outDirectionValue = 0;
                  $inDirection = 2;
                  $inDirectionValue = 0;
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
              $sbcDirection = 1;
              $outDirection = 1;
              $outDirectionValue = 1;
              $inDirection = 2;
              $inDirectionValue = 0;
            } else {
              Log::info("Yêu cầu chặn chiều gọi vào cho khách đang mở 2 chiều => Chặn gọi vào");
              $resultState = 12;
              $resultStatus = 1;
              $sbcDirection = 2;

              $outDirection = 1;
              $outDirectionValue = 0;
              $inDirection = 2;
              $inDirectionValue = 1;
            }
          } else {
            Log::info("Đang mở rồi, Gửi lệnh mở sẽ không giải quyết gì ");
          }

          break;
      }

      Log::info("Savel SBC: new status" . $resultStatus);
      Log::info("State" . $resultState);

      if (isset($resultStatus) && $resultStatus > -1 && $resultState) {

        $resultStatus= $numway==2?$newStatus:$resultStatus;

        $resultState= $numway==2?10:$resultState;

        $outDirectionValue= $numway==2?$newStatus:$outDirectionValue;
        $inDirectionValue= $numway==2?$newStatus:$inDirectionValue;

        if(count($listHotlineOk)>0 )
        {
          $line->status = $resultStatus;
          $line->pause_state = $resultState;

          $line->save();


          //Cập nnhật caller group
          //
          //

          $CallerGroup = SBCCallGroup::where('caller', $hotlineNo)->where('cus_id', $customer->id)->first();

          if ($CallerGroup) {
            $CallerGroup->status = $outDirectionValue;
            $CallerGroup->save();
          }

          $logAction = $sbcDirection == 1 ? config("sbc.action.pause_call_out") : config("sbc.action.pause_call_in");

            SBCRouting::where('i_customer', $customer->id)->where('direction', $outDirection)->where('caller', $hotlineNo)->update(["status" => $outDirectionValue]);
            SBCRouting::where('i_customer', $customer->id)->where('direction', $inDirection)->where('callee', $hotlineNo)->update(["status" => $inDirectionValue]);
            Log::info("SAVE SBC oN PRIMARY");

            $this->SetActivity($request->all(), 'hot_line_config', $customer->id, 0, $logAction, ($newStatus == 1 ? "Chặn" : "Mở") . " 1 chiều hotline " . $customer->companyname, $enterprise_number, $hotlineNo);


          $CDRAction = $sbcDirection == 1 ? config("sbc.action_cdr.pause_call_out_cdr") : config("sbc.action_cdr.pause_call_in_cdr");
          //        $CDR= $request->enterprise_number."|$CDRAction|".date("YmdHis");
          $CDR = $enterprise_number . "|$CDRAction|" . date("YmdHis") . "|" . $hotlineNo;

          $this->CDRActivity($customer->server_profile, $CDR, $request->enterprise_number, "PAUSE_STATE_HOTLINE");

        }
        else
        {
          Log::info("HOTLINE:".$hotlineNo.' CHANGE PAUSE STATE UNSUCCESSFULLY');
        }

      }

      return response()->json(["status" => true], 200);
    }

    public function changeDirectionStatus(Request $request) {
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;

      if (!$this->checkEntity($user->id, "CONFIG_HOTLINE")) {
        Log::info($user->email . '  TRY TO GET V1HotlineController.changeDirectionStatus WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

      $validator = Validator::make($request->all(), ['enterprise_number' => 'required|alpha_dash|max:250', 'hotline_numbers' => 'required|alpha_num|max:1500', 'prefixs' => 'required|prefix_valid|max:250', 'status' => 'required|in:0,1',

      ]);
      if ($validator->fails()) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|CHANGE_HOTLINE_PROFILE|" . $logDuration . "|CHANGE_HOTLINE_PROFILE_FAIL Invalid input data");
        /** @var LOG $logDuration */

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }

      $enterprise = $request->enterprise_number;
      $blockOrAllow = request('status', 0); // 0 là chăn, 1 là mở
      $prefix = request('prefixs', "");
      // Check in Enterprisse

      $cus = Customers::where("enterprise_number", $enterprise)->whereIn("blocked", [0, 1])->first();

      if (!$cus) {
        Log::info("NOT FOUND CUSTOMER BY ENTERPRISE:" . $enterprise);
        return $this->ApiReturn(['enterprise_numbers' => ['The selected enterprise number is invalid']], false, 'The given data was invalid', 422);
      }

      $lstHotlines = [];
      if (strpos($request->hotline_numbers, ',') !== false) {
        $lstHotlines = [$request->hotline_numbers];
      } else {
        $lstHotlines = explode(',', $request->hotline_numbers);
      }

      if (strpos($prefix, ',') !== false) {
        $lstPrefix = explode(',', $prefix);
      } else {
        $lstPrefix = [$prefix];
      }

      // Check existst list
      $hotlineValidLists = Hotlines::where("cus_id", $cus->id)->whereIn('hotline_number', $lstHotlines)->whereIn('status', [0, 1])->get();

      if (count($hotlineValidLists) == 0 || count($hotlineValidLists) != count($lstHotlines)) {
        Log::info("NOT FOUND HOTLINE  ON ENTERPRISE:" . $enterprise);
        return $this->ApiReturn(['hotline_numbers' => ['The hotline numbers not found']], false, 'The given data was invalid', 422);
      }

      // Nếu chặn thfi thay thế chuỗi
      $lst1900 = [];
      $lstInter = [];

      $prfix1900 = '/^[1800\1900][0-9]{3,9}+$/m';
      $prfixInter = '/^[0\+][0-9]{1,15}+$/m';

      if ($blockOrAllow == 0) {
        $action = "Chặn";

        foreach ($lstPrefix as $pre) {
          if (preg_match($prfix1900, $pre)) {
            array_push($lst1900, $pre);
          }

          if (preg_match($prfixInter, $pre)) {
            array_push($lstInter, $pre);
          }
        }
        // Tìm mã quốc tế
        // Tìm mã 1900 hoặc 1800
        // Tìm mã quốc tế tất cả

      } else {
        $action = "Mở";
        // Mở 1 quốc gia
        // Mở tất quốc gia
        // Mở 1900
        // Mở 1800
      }

      $blockRegexCalleeOrigin = "^(00|\\+84|1900|1800).*";
      $blockRegexCalleeNew = "^0[1-9][0-9]{7,9}$|";
      //  $^(18001881|19001111)[0-9]{4,6}$|

      //  ^(0085|0086|0088)[0-9]{8,15}$";

      if (count($lst1900) > 0) {
        $blockRegexCalleeNew .= "$^(" . implode("|", $lst1900) . ")[0-9]{4,6}$|";
      }

      if (count($lstInter) > 0) {
        $blockRegexCalleeNew .= "^(" . implode("|", $lstInter) . ")[0-9]{8,15}$";
      }

      return ['s' => $lstPrefix, 'action' => $action, '1900' => $lst1900, 'inter' => $lstInter, 'stringRegex' => $blockRegexCalleeNew];
      // nếu mở thì check chuỗi

      DB::beginTransaction();
      try {
        foreach ($hotlineValidLists as $line) {
          $routing = SBCRouting::where('caller', $line->hotline_number)->where('i_customer', $cus->id)->first();

          $acl = SBCAcl::where('i_acl', $routing->i_acl)->first();
          if ($blockOrAllow == 1) {
            $acl->block_regex_callee = $prefix;
            Log::info("UPDATE_PREFIX_HOTLINE:" . $line->hotline_number . "|NEW_BLOCK_PREFIX:" . $prefix);
          } else {
            $acl->allow_regex_callee = $prefix;
            Log::info("UPDATE_PREFIX_HOTLINE:" . $line->hotline_number . "|NEW_ALLOW_PREFIX:" . $prefix);
          }
          $acl->save();
        }

        DB::commit();
      } catch (\Exception $exception) {
        DB::rollback();
        Log::info("ERROR_EXCEPTION_CAUSE_ON_UPDATE_SIP_PREFIX:" . $enterprise);
        Log::info($exception->getTraceAsString());
        return $this->ApiReturn(['system' => ['Error on update']], false, 'System error on update', 500);
      }

      return $this->ApiReturn(null, true, null, 200);
    }

  }


