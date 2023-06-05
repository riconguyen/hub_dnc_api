<?php

namespace App\Http\Controllers;

use App\CallFeeCycleStatus;
use App\CallFeeCycleStatusBackup;
use App\Customers;

use App\CustomersBackup;
use App\Hotlines;
use App\HotlinesBackup;
use App\QuantityCycleStatusBackup;
use App\QuantitySubcriber;
use App\QuantitySubcriberBackup;
use App\QuantitySubscriberLocalCycleStatus;
use App\QuantitySubscriberLocalCycleStatusBackup;
use App\SBCAcl;
use App\SBCAclBackup;
use App\SBCRouting;
use App\SBCRoutingBackup;
use App\ServiceConfig;
use App\ServiceConfigBackup;
use App\ServiceSubcriber;
use App\ServiceSubcriberBackup;
use App\SubChargeFeeCycleBackup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;

class MoveCustomerController extends Controller
{
    //



  public function postServerMoveCustomer(Request $request)
  {
    $startTime=round(microtime(true) * 1000);
    $user = $request->user();


    if (!$this->checkEntity($user->id, "CHANGE_SERVER_CUSTOMER")) {
      Log::info($user->email . '  TRY TO GET MoveCustomerController.postServerMoveCustomer WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $API_STATE= $request->api_source?"API|":"WEB|";

    $enterprise_number= $request->enterprise_number;
    $zeroEnter_number= $this->removeZero($enterprise_number);
    $customer= Customers::where('enterprise_number',$enterprise_number)->first();
    $service= ServiceConfig::where("id",$customer->service_id)->first();

    // Check Customer In use in server
    if(!$customer)
    {
      return ['message'=>"Không tìm thấy khách hàng",'status'=>false];
    }

    if($customer->server_profile != config("server.server_profile"))
    {
      return response()->json(['status' => false, 'message'=> 'Khách hàng này đang hoạt động ở server khác','c'=>$customer->server_profile,
        'con'=>config("server.server_profile")], 400);
    }
    $listCurrentHotline= Hotlines::where("cus_id",$customer->id)->whereIn("status",[0,1])->get();


    $customerBackup=CustomersBackup::where('enterprise_number',$enterprise_number)->whereIn('blocked',[0,1])->first();

    if(!$customerBackup )
    {
      Log::info("Không  tìm thấy thông tin khách hàng server backup");
            return ['message'=>"Không  tìm thấy thông tin khách hàng server backup",'status'=>false];
    }
    $backupService= ServiceConfigBackup::where("product_code",$service->product_code)->first();
    if(!$backupService )
    {
      Log::info("Không  tìm thấy thông tin dịch vụ trên ".$service->product_code." server backup");
      return ['message'=>"Not found sync product_code".$service->product_code,'status'=>false];
    }

    // Cắt cước
    $callCycle= DB::select("select id, enterprise_number, cycle_from, cycle_to,  type, call_type, total_duration, total_amount
from call_fee_cycle_status  where enterprise_number=? and cycle_from =?",[$zeroEnter_number,date("Y-m-01 00:00:00")]);
    $subCycle= DB::select("select id, enterprise_number, cycle_from, cycle_to, total_amount from subcharge_fee_cycle_status  
where enterprise_number=? and cycle_from =?",[$zeroEnter_number,date("Y-m-01 00:00:00")]);

    $QuantityCycle= DB::select("select id, enterprise_number, cycle_from, cycle_to, activated,type,total_reserve,reserve_duration 
      from quantity_subcriber_cycle_status  where enterprise_number=? and cycle_from =?",[$zeroEnter_number,date("Y-m-01 00:00:00")]);


    $quantityLocal=  DB::select("select enterprise_number, cycle_from, cycle_to,  total_reserve, total_amount, is_charge, type,updated_at,created_at
from quantity_subcriber_local_cycle_status  where enterprise_number=? and cycle_from =?",[$zeroEnter_number,date("Y-m-01 00:00:00")]);



    // Check cước máy chủ backup
    $callCycleBackup= DB::connection("db2")->select("select id,  enterprise_number,  type, call_type,  cycle_from, cycle_to, total_duration, total_amount from call_fee_cycle_status  where enterprise_number=? and cycle_from =?",[$zeroEnter_number,date("Y-m-01 00:00:00")]);
    $subCycleBackup= DB::connection("db2")->select("select id, enterprise_number, cycle_from, cycle_to, total_amount from subcharge_fee_cycle_status  where enterprise_number=? and cycle_from =?",[$zeroEnter_number,date("Y-m-01 00:00:00")]);
    $QuantityCycleBackup= DB::connection("db2")
      ->select("select id, enterprise_number, cycle_from, cycle_to, activated,type,total_reserve,reserve_duration 
      from quantity_subcriber_cycle_status  where enterprise_number=? and cycle_from =?",[$zeroEnter_number,date("Y-m-01 00:00:00")]);



    if(count($listCurrentHotline)>0)
    {
      $lstHotlineToCheck=[];
      foreach ($listCurrentHotline as $item)
      {
        array_push($lstHotlineToCheck, $item->hotline_number);
      }

      $resBackupHotline= HotlinesBackup::whereIn("status",[0,1])->whereIn("hotline_number",$lstHotlineToCheck)->get();
      if(count($resBackupHotline)==0)
      {
        // Không có Hotline nào dang active trên server backup
        Log::info("Không có active hotline trên server backup");
      }

    }


    DB::beginTransaction();
    DB::connection("db2")->beginTransaction();

    try{




      // DISABLE HOTLINE
      $this->SetActivity(['blocked' =>1, 'updated_at' => date("Y-m-d H:i:s")], "customers", $customer->id, 0,config("sbc.action.move_customer"),"Khóa khách hàng trên server ".config("server.server_profile"), $enterprise_number, null);

      $hotlines = DB::table('hot_line_config')
        ->where('cus_id', $customer->id)
        ->whereIn('status',[0,1])
        ->select('hotline_number', 'id','status', 'init_charge','updated_at','last_charge_date','pause_state')
        ->get();


      foreach ($hotlines as $line) {
        DB::table('sbc.routing')->where("i_customer",$customer->id)
          ->where('caller', $line->hotline_number)->update(['status' => 1]);
        DB::table('sbc.routing')->where("i_customer",$customer->id)
          ->where('callee', $line->hotline_number)->update(['status' => 0]);



        DB::connection("db2")->table('sbc.routing')->where("i_customer",$customerBackup->id)
          ->where('caller', $line->hotline_number)->update(['status' => $line->status]);


        DB::connection("db2")->table('sbc.routing')->where("i_customer",$customerBackup->id)
          ->where('callee', $line->hotline_number)->update(['status' =>  $line->status]);

        DB::connection("db2")->table('hot_line_config')->where('cus_id', $customerBackup->id)
          ->whereIn('status', [0, 1])->update(['status' => $line->status,'pause_state'=>$line->pause_state, 'init_charge'=>$line->init_charge,'updated_at'=>$line->updated_at,'last_charge_date'=>$line->last_charge_date]);


        // Disable hotline 
        DB::table('hot_line_config')->where('cus_id', $customer->id)
          ->whereIn('status', [0, 1])->update(['status' => 1,'pause_state'=>11]);

        $CDR = $customer->enterprise_number . "|1|" . date("YmdHis") . "|" . $line->hotline_number;
        $this->CDRActivity($customer->server_profile, $CDR, $customer->enterprise_number, $API_STATE . "PAUSE_STATE_HOTLINE");
        $this->SetActivity(json_encode($line), "hot_line_config", $line->id, 0,config("sbc.action.move_customer"),"Khóa chiều gọi ra trên server  ".config("server.server_profile"), $enterprise_number, $line->hotline_number);



        $CDRBackup = $customerBackup->enterprise_number . "|0|" . date("YmdHis") . "|" . $line->hotline_number;
        $this->CDRActivity(config("server.server_profile_backup"), $CDRBackup, $customerBackup->enterprise_number, $API_STATE . "RESUME_STATE_HOTLINE");
        $this->SetActivity(json_encode($line), "hot_line_config", $line->id, 0,config("sbc.action.move_customer"),"Mở hai chiều hotline trên server ".config("server.server_profile_backup"), $enterprise_number, $line->hotline_number);


      }
      $serviceSubcriber= ServiceSubcriber::where("id",$customer->id)->first();
      $serviceSubcriberBackup = ServiceSubcriberBackup::where('id', $customerBackup->id)->whereIn("status",[0,1])->first();


      $CDR = $customer->enterprise_number . "|1|" . date("YmdHis") . "|" . $customer->product_code;
      $this->CDRActivity(config("server.server_profile"), $CDR, $customer->enterprise_number, $API_STATE . "PAUSE_STATE_CUSTOMER");

      // Enable DB2;




      if ($serviceSubcriberBackup) {
        $serviceSubcriberBackup->begin_charge_date = $serviceSubcriber->begin_charge_date;
        $serviceSubcriberBackup->expired_contract_date = $serviceSubcriber->expired_contract_date;
        $serviceSubcriberBackup->last_charge_date = $serviceSubcriber->last_charge_date;
        $serviceSubcriberBackup->last_charge_sub_status = $serviceSubcriber->last_charge_sub_status;
        $serviceSubcriberBackup->last_try_charge = $serviceSubcriber->last_try_charge;
        $serviceSubcriberBackup->user_number = $serviceSubcriber->user_number;
        $serviceSubcriberBackup->status=$serviceSubcriber->status;
        $serviceSubcriberBackup->updated_at=date("Y-m-d H:i:s");
        $serviceSubcriberBackup->save();

        $serviceSubcriber->status=1;
        $serviceSubcriber->save();

      }



      $serviceQuantitySubcriber= QuantitySubcriber::where("service_subcriber_id",$customer->id)
        ->where('status',0)
        ->first();

      $checkQuantityConfig= DB::connection("db2")->table("quantity_config")->where("service_config_id",$backupService->id)
        ->where("status",0)
        ->first();


      if(count($quantityLocal)>0)
      {

        foreach ($quantityLocal as $item)
        {
          $qtyLocal= new QuantitySubscriberLocalCycleStatusBackup();
          $qtyLocal->enterprise_number= $item->enterprise_number;
          $qtyLocal->created_at= $item->created_at;
          $qtyLocal->updated_at= $item->updated_at;
          $qtyLocal->cycle_from= $item->cycle_from;
          $qtyLocal->cycle_to= $item->cycle_to;
          $qtyLocal->total_reserve= $item->total_reserve;
          $qtyLocal->total_amount= $item->total_amount;
          $qtyLocal->type= $item->type;
          $qtyLocal->is_charge= $item->is_charge;
          $qtyLocal->save();
        }

        QuantitySubscriberLocalCycleStatus::where("enterprise_number",$zeroEnter_number)->where("cycle_from", date("Y-m-01 00:00:00"))->delete();

      }

      if ($serviceQuantitySubcriber) {
        $checkServiceQuantityOnBackup = DB::connection("db2")->select("select q.id from quantity_subcriber q join quantity_config qc on qc.id= q.quantity_config_id
            and qc.service_config_id=? and q.service_subcriber_id=? and q.status=1 order by q.id desc ", [$backupService->id, $customerBackup->id]);
        if (count($checkServiceQuantityOnBackup) == 1) {
          $serviceQuantityBackup = QuantitySubcriberBackup::where("id", $checkServiceQuantityOnBackup[0]->id)->first();

          $serviceQuantityBackup->status = $serviceQuantitySubcriber->status;
          $serviceQuantityBackup->resub = $serviceQuantitySubcriber->resub;
          $serviceQuantityBackup->begin_use_date = $serviceQuantitySubcriber->begin_use_date;
          $serviceQuantityBackup->init_charge = $serviceQuantitySubcriber->init_charge;
          $serviceQuantityBackup->last_charge_date = $serviceQuantitySubcriber->last_charge_date;
          $serviceQuantityBackup->last_charge_sub_status = $serviceQuantitySubcriber->last_charge_sub_status;
          $serviceQuantityBackup->last_try_charge_date = $serviceQuantitySubcriber->last_try_charge_date;
          $serviceQuantityBackup->save();

          $serviceQuantitySubcriber->status = 1; // Hủy gói trên site cũ // Lưu ý chỉ hủy 1 row, có thể leak nếu có nhiều hơn 1 row
          $serviceQuantitySubcriber->resub = 0; // Hủy gói trên site cũ // Lưu ý chỉ hủy 1 row, có thể leak nếu có nhiều hơn 1 row

          $serviceQuantitySubcriber->save();
        } else {

          DB::rollback();
          DB::connection("db2")->rollback();

          return $this->ApiReturn(["product_code"=>["Not found quantity config package on backup server"]], false, "Not found quantity package on backup server", 500);
        }

      }


      $this->SetActivity(['status' =>0, 'updated_at' => date("Y-m-d H:i:s")], "customers", $customerBackup->id, 0,config("sbc.action.move_customer"),"Mở khách hàng  trên server ".config("server.server_profile_backup"), $enterprise_number, null);



      $CDR = $customerBackup->enterprise_number . "|0|" . date("YmdHis") . "|" . $backupService->product_code;
      $this->CDRActivity(config("server.server_profile_backup"), $CDR, $customerBackup->enterprise_number, $API_STATE . "OPEN_CUSTOMER");
      $customerBackup->server_profile=config("server.server_profile_backup");
      $customer->server_profile=config("server.server_profile_backup");




      $customerBackup->blocked=$customer->blocked; // Mở khóa Server backup
      $customerBackup->pause_state=$customer->pause_state; // Mở khóa Server backup

      $customer->blocked=1;
      $customer->pause_state=11;
      $customer->save();
      $customerBackup->save();

//       Chèn cước mới

      if(count($callCycle) >0)
      {

        // Log cước cũ trước khi xóa
        if(count($callCycleBackup)>0)
        {
          foreach ($callCycleBackup as $item)
          {
            $this->SetActivityBackup($item, "call_fee_cycle_status", $item->id, 0,config("sbc.action.update_cycle_charge"),
              "Cước thoại thay đổi do chuyển site: Thông tin hiện thời $item->enterprise_number| $item->total_amount | $item->total_duration | $item->type | $item->call_type| $item->cycle_from|  $item->cycle_to", $enterprise_number, null);
            CallFeeCycleStatusBackup::where("id",$item->id)->delete();
          }

        }


        foreach ($callCycle as $item)
        {
          Log::info(json_encode($item));

          $Cycle= new CallFeeCycleStatusBackup();
          $Cycle->enterprise_number= $item->enterprise_number;
          $Cycle->cycle_from= $item->cycle_from;
          $Cycle->cycle_to= $item->cycle_to;
          $Cycle->call_type= $item->call_type;
          $Cycle->type= $item->type;
          $Cycle->total_amount= $item->total_amount;
          $Cycle->total_duration= $item->total_duration;
          $Cycle->created_at= date("Y-m-d H:i:s");
          $Cycle->updated_at= date("Y-m-d H:i:s");
          $Cycle->save();

          $this->SetActivityBackup($item, "call_fee_cycle_status", $Cycle->id, 0,config("sbc.action.update_cycle_charge"),
            "Cước thoại thay đổi do chuyển site: Thông tin thay đổi thành $item->enterprise_number| $item->total_amount |  $item->total_duration | $item->type | $item->call_type|$item->cycle_from|  $item->cycle_to", $enterprise_number, null);


        }

       }


      if(count($subCycle) >0)
      {
        if(count($subCycleBackup)>0)
        {
          foreach ($subCycleBackup as $item)
          {
            $this->SetActivityBackup($item, "call_fee_cycle_status", $item->id, 0,config("sbc.action.update_cycle_charge"),
              "Cước sub thay đổi do chuyển site: Thông tin hiện thời $item->enterprise_number| $item->total_amount | $item->cycle_from|  $item->cycle_to| ", $enterprise_number, null);

            SubChargeFeeCycleBackup::where("id",$item->id)->delete();
          }



        }



        foreach ($subCycle as $sub)
        {

          Log::info($sub->enterprise_number);


          $subCharge= new SubChargeFeeCycleBackup();
          $subCharge->enterprise_number= $sub->enterprise_number;
          $subCharge->cycle_from= $sub->cycle_from;
          $subCharge->cycle_to= $sub->cycle_to;
          $subCharge->total_amount= $sub->total_amount;
          $subCharge->created_at= date("Y-m-d H:i:s");
          $subCharge->updated_at= date("Y-m-d H:i:s");
          $subCharge->save();

          $this->SetActivityBackup($sub, "call_fee_cycle_status", $subCharge->id, 0,config("sbc.action.update_cycle_charge"),
            "Cước sub thay đổi do chuyển site: Thông tin thay đổi thành
             $sub->enterprise_number| $sub->total_amount | $sub->cycle_from|  $sub->cycle_to| ", $enterprise_number, null);
        }
      }
      if(count($QuantityCycle) >0)
      {
        if(count($QuantityCycleBackup)>0)
        {
          foreach ($QuantityCycleBackup as $item)
          {
            $this->SetActivityBackup($item, "quantity_subcriber_cycle_status", $item->id, 0,config("sbc.action.update_cycle_charge"),
              "Gói sản lượng thay đổi: Thông tin hiện thời $item->enterprise_number| $item->reserve_duration |$item->total_reserve | $item->cycle_from|  $item->cycle_to| ", $enterprise_number, null);

            QuantityCycleStatusBackup::where("id",$item->id)->delete();
          }



        }



        foreach ($QuantityCycle as $sub)
        {


          $subCharge= new QuantityCycleStatusBackup();
          $subCharge->enterprise_number= $sub->enterprise_number;
          $subCharge->cycle_from= $sub->cycle_from;
          $subCharge->cycle_to= $sub->cycle_to;
          $subCharge->reserve_duration= $sub->reserve_duration;
          $subCharge->total_reserve= $sub->total_reserve;
          $subCharge->activated= $sub->activated;
          $subCharge->type= $sub->type;
          $subCharge->created_at= date("Y-m-d H:i:s");
          $subCharge->updated_at= date("Y-m-d H:i:s");
          $subCharge->save();

          $this->SetActivityBackup($sub, "call_fee_cycle_status", $subCharge->id, 0,config("sbc.action.update_cycle_charge"),
            "Gói sản lượng thay đổi  do chuyển site: Thông tin thay đổi thành   
                $sub->enterprise_number| $sub->reserve_duration |$sub->total_reserve |  $sub->cycle_from|  $sub->cycle_to| ", $enterprise_number, null);
        }
      }

      // Chèn cước kết thúc

      DB::commit();
      DB::connection("db2")->commit();
    }
    catch (\Exception $exception)
    {

      Log::info($exception);

      DB::rollback();
      DB::connection("db2")->rollback();

      return response()->json(['status'=>false],400);

    }

    return ['status'=>true];
  }


  public function mirgradeServer(Request $request)
  {
    $startTime=round(microtime(true) * 1000);
    $user = $request->user();
    $mode= $request->mode;
    $limit= $request->from?$request->from:0;
    $enterprises= $request->enterprises?$request->enterprises:null;
    $take= $request->take?$request->take:2000;
    if ($user->role != ROLE_ADMIN && $user->role != ROLE_CS) {

      return response()->json(['error' => 'Permission denied'], 403);
    }
    $API_STATE= $request->api_source?"API|":"WEB|";






    // Chuẩn bị
    $sql="select c.*, s.product_code, ss.num_agent, ss.begin_charge_date from customers c join service_subcriber ss on ss.id=c.id  left join service_config s
 on c.service_id= s.id where c.blocked in (0,1) and c.server_profile=?  ";


    $param=[config("server.server_profile")];

    if($enterprises)
    {

      $sql .= " and c.enterprise_number in (?)  ";

      array_push($param, $enterprises);

    }

    $sql.= " ORDER BY ID DESC  LIMIT ?,? ";

    array_push($param, $limit, $take);
    $lstCustomer= DB::select($sql,$param);


    Log::info($lstCustomer);
    $existsUserOnBackup=[];

    $totalCustomer= count($lstCustomer);
    $totalCustomerSuccess= 0;
    $totalHotline= 0;
    // Danh sách hotline





    DB::beginTransaction();

    DB::connection("db2")->beginTransaction();

//    try{
      foreach ($lstCustomer as $customer) {

        // Check exsist();


        $lstHotlines= Hotlines::where("cus_id",$customer->id)->whereIn("status",[0,1])->get();

        foreach ($lstHotlines as $line)
        {
          $line->routingCallee= SBCRouting::where("i_customer",$customer->id)->where('callee',$line->hotline_number)->where("status",0)->first();
          $line->routingCaller= SBCRouting::where("i_customer",$customer->id)->where('caller',$line->hotline_number)->where("status",0)->first();

          if($line->routingCaller && $line->routingCaller->i_acl)
          {
            $line->acl=SBCAcl::where("i_acl",$line->routingCaller->i_acl)->first();
          }

          if($line->routingCaller && ($line->routingCaller->i_acl != $line->routingCaller->i_acl_backup))
          {
            $line->acl_backup=SBCAcl::where("i_acl",$line->routingCaller->i_acl_backup)->first();
          }

        }

        $customer->hotlines= $lstHotlines;

        $customerBackup= CustomersBackup::where("enterprise_number",$customer->enterprise_number)->whereIn("blocked",[0,1])->first();

        if($customerBackup)
        {
          // Lỗi không tạo được khách hàng do đã tồn tại
          $this->SetActivity([],'customers',$customerBackup->id,0,'Đồng bộ khách hàng',"Đã tồn tại khách hàng trên server backup ".$customerBackup->enterprise_number, $customerBackup->enterprise_number,"");

          array_push($existsUserOnBackup,$customerBackup);
        }
        else
        {
          // Tìm dịch vụ
          $serviceBackup = ServiceConfigBackup::where("product_code", $customer->product_code)->where('status', 0)->first();

          if ($serviceBackup && $mode=="SYNC") {
          
//              $newCustomerBackupData = $customer->only('cus_name', 'enterprise_number', 'companyname', 'addr', 'phone1',
//                'email', 'ip_auth', 'ip_proxy', 'destination', 'ip_auth_backup', 'ip_proxy_backup');

            $newCustomerBackupData= new CustomersBackup();
            $newCustomerBackupData->cus_name= $customer->cus_name;
            $newCustomerBackupData->enterprise_number= $customer->enterprise_number;
            $newCustomerBackupData->companyname= $customer->companyname;
            $newCustomerBackupData->addr= $customer->addr;
            $newCustomerBackupData->phone1= $customer->phone1;
            $newCustomerBackupData->email= $customer->email;
            $newCustomerBackupData->ip_auth= $customer->ip_auth;
            $newCustomerBackupData->ip_proxy= $customer->ip_proxy;
            $newCustomerBackupData->destination= $customer->destination;
            $newCustomerBackupData->ip_auth_backup= $customer->ip_auth_backup;
            $newCustomerBackupData->ip_proxy_backup= $customer->ip_proxy_backup;
            $newCustomerBackupData->blocked= 1;
            $newCustomerBackupData->pause_state= 12; // Luôn mở chiều gọi vào
            $newCustomerBackupData->service_id= $serviceBackup->id;
            $newCustomerBackupData->server_profile= config("server.server_profile");
            $newCustomerBackupData->save();

              $newCustomerBackupId =$newCustomerBackupData->id;


                $postData = [
                  "service_config_id" =>$serviceBackup->id,
                  "enterprise_number" => $customer->enterprise_number,
                  "status" => 1,
                  "id" =>$newCustomerBackupId,
                  "num_agent" => $customer->num_agent,
                  "updated_at" => date('Y-m-d H:i:s'),
                  "begin_charge_date" => $customer->begin_charge_date
                ];
                $subid = DB::connection("db2")->table('service_subcriber')
                    ->insert($postData);
              $this->SetActivity([], 'customers', $newCustomerBackupId, 0, 'Đồng bộ khách hàng - tạo khách', "[BACKUP] Tạo khách hàng  server backup " . $customer->enterprise_number, $customer->enterprise_number, "");

              // Lặp Hotline

              if (count($customer->hotlines) > 0) {
                foreach ($customer->hotlines as $hotline) {
                  $hotlineBackup = HotlinesBackup::where("hotline_number", $hotline->hotline_number)->whereIn("status", [0, 1])->first();

                  if($hotlineBackup)
                  {
                    $this->SetActivity([],'hot_line_config',$hotlineBackup->id,0,'Đồng bộ khách hàng',"Đã tồn tại hotline server backup  đang nằm trên khách: ".$hotlineBackup->enterprise_number, $hotlineBackup->enterprise_number,$hotlineBackup->hotline_number);

                  }
                  else
                  {

                    $totalHotline++;
                    // Tiến trình tạo khách
                    $newDataHotline= new HotlinesBackup();
                    $newDataHotline->hotline_number= $hotline->hotline_number;
                    $newDataHotline->status= 1; // Luôn khóa
                    $newDataHotline->pause_state= 12; // Khóa chiều gọi ra
                    $newDataHotline->init_charge= 1;  // Đã thu tiền khởi tạo
                    $newDataHotline->enterprise_number= $newCustomerBackupData['enterprise_number'];
                    $newDataHotline->cus_id= $newCustomerBackupId;
                    $newDataHotline->save();
                    $this->SetActivity([], 'hot_line_config', $newDataHotline->id, 0, 'Đồng bộ khách hàng - tạo hotline', "[BACKUP] 1.1 Tạo  hotline  server backup " . $customer->enterprise_number, $customer->enterprise_number, $newDataHotline->hotline_number);


                    // Tạo ACL
                    if(isset($hotline->acl))
                    {


                      $aclMainData= $hotline->acl;
                      $aclMain= new SBCAclBackup();
                      $aclMain->ip_auth= $aclMainData->ip_auth;
                      $aclMain->ip_proxy= $aclMainData->ip_proxy;
                      $aclMain->block_regex_caller= $aclMainData->block_regex_caller;
                      $aclMain->block_regex_callee= $aclMainData->block_regex_callee;
                      $aclMain->allow_regex_caller= $aclMainData->allow_regex_caller;
                      $aclMain->allow_regex_callee= $aclMainData->allow_regex_callee;
                      $aclMain->description= $aclMainData->description;
                      $aclMain->save();
                      $i_acl = $aclMain->i_acl;
                      $i_acl_backup = $aclMain->i_acl;

                      $this->SetActivity([], 'hot_line_config', $newDataHotline->id, 0, 'Đồng bộ khách hàng - tạo acl', "[BACKUP] 1.2 Tạo  hotline acl " . $customer->enterprise_number, $customer->enterprise_number, $newDataHotline->hotline_number);


                      if(isset($hotline->acl_backup))
                      {

                        //->only('ip_auth','ip_proxy','block_regex_caller','block_regex_callee','allow_regex_caller','allow_regex_callee','description');

                        $aclMainDataBackup= $hotline->acl;
                        $aclMainBk= new SBCAclBackup();
                        $aclMainBk->ip_auth= $aclMainDataBackup->ip_auth;
                        $aclMainBk->ip_proxy= $aclMainDataBackup->ip_proxy;
                        $aclMainBk->block_regex_caller= $aclMainDataBackup->block_regex_caller;
                        $aclMainBk->block_regex_callee= $aclMainDataBackup->block_regex_callee;
                        $aclMainBk->allow_regex_caller= $aclMainDataBackup->allow_regex_caller;
                        $aclMainBk->allow_regex_callee= $aclMainDataBackup->allow_regex_callee;
                        $aclMainBk->description= $aclMainDataBackup->description;
                        $aclMainBk->save();

                        $i_acl_backup = $aclMainBk->i_acl;


                      }



                      $totalCustomerSuccess ++;



                      // Tạo routing
                      Log::info("ERROR TRACKING 1");

                      if($hotline->routingCaller)
                      {
                        $hotlineRoutingCaller= new SBCRoutingBackup();
                        $hotlineRoutingCaller->direction=1;
                        $hotlineRoutingCaller->caller=$hotline->hotline_number;
                        $hotlineRoutingCaller->i_acl= $i_acl;
                        $hotlineRoutingCaller->i_acl_backup= $i_acl_backup;
                        $hotlineRoutingCaller->destination= $hotline->routingCaller->destination;
                        $hotlineRoutingCaller->priority= $hotline->routingCaller->priority;
                        $hotlineRoutingCaller->i_vendor= $hotline->routingCaller->i_vendor;
                        $hotlineRoutingCaller->i_customer= $newCustomerBackupId;
                        $hotlineRoutingCaller->description= $customer->enterprise_number;
                        $hotlineRoutingCaller->network= $hotline->routingCaller->network;
                        $hotlineRoutingCaller->i_sip_profile= $hotline->routingCaller->i_sip_profile;

                        $hotlineRoutingCaller->status= 1; // Chặn gọi ra
                        $hotlineRoutingCaller->save();
                      }



                      if($hotline->routingCallee) {
                        Log::info("ERROR TRACKING 2");

                        $hotlineRoutingCallee = new SBCRoutingBackup();
                        $hotlineRoutingCallee->direction = 2;
                        $hotlineRoutingCallee->callee = $hotline->hotline_number;
                        $hotlineRoutingCallee->i_acl = 1;
                        $hotlineRoutingCallee->i_acl_backup = 2;
                        $hotlineRoutingCallee->destination = $hotline->routingCallee->destination;
                        $hotlineRoutingCallee->priority = 10;
                        $hotlineRoutingCallee->i_vendor = 0;
                        $hotlineRoutingCallee->i_customer = $newCustomerBackupId;
                        $hotlineRoutingCallee->description = $customer->enterprise_number;
                        $hotlineRoutingCallee->network = 2;
                        $hotlineRoutingCallee->i_sip_profile = 2;
                        $hotlineRoutingCallee->auto_detect_blocking = $hotline->routingCallee->auto_detect_blocking;
                        $hotlineRoutingCallee->status = 0; // Luôn mở cho chiều gọi vào
                        $hotlineRoutingCallee->save();

                        Log::info("ERROR TRACKING 3");
                      }


                    }
                    // Tạo ACL



                      /** @var  ROUTING   $arrRoutingPrimary */







                  }
                }
              }

          }

        }



      }
    $this->SetActivity([], 'customer', 0, 0, 'Đồng bộ khách hàng - Hoàn tất', "[BACKUP]  Lênh chuyển $totalCustomer khách hàng. Đã chuyển thành công $totalCustomerSuccess khách hàng và $totalHotline hotline sang máy chủ ".config("server.server_profile_backup") ,null, null);

      DB::commit();
      DB::connection("db2")->commit();
//    }
//    catch (\Exception $exception)
//    {
//
//
//      DB::rollback();
//      DB::connection("db2")->rollback();
//
//      return ['err'=>$exception->getTraceAsString()];
//
//    }


    return [ 'message'=>"[BACKUP]  Thực hiện chuyển $totalCustomer khách hàng:  Đã chuyển $totalCustomerSuccess và  $totalHotline hotline thành công , có ".count($existsUserOnBackup) ." đã tồn tại,không đồng bộ được,  xem thêm ở danh sách sang máy chủ,  ".config("server.server_profile_backup"), ];
  }
}
