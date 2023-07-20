<?php

namespace App\Http\Controllers;

use App\ActivityBackup;
use App\CDRActivity;
use App\CDRActivityBackup;
use App\ChargeLog;
use App\ChargeLogBackup;
use App\Customers;
use App\CustomersBackup;
use App\Entity;
use App\Hotlines;
use App\HotlinesBackup;
use App\QuantitySubcriber;
use App\QuantitySubcriberBackup;
use App\RoleEntity;
use App\Roles;
use App\SBCAcl;
use App\SBCAclBackup;
use App\SBCRouting;
use App\SBCRoutingBackup;
use App\ServiceSubcriber;
use App\ServiceSubcriberBackup;
use App\SubChargeFeeCycle;
use App\SubChargeFeeCycleBackup;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Activity;

define('ROLE_ADMIN',1);
define('ROLE_BILLING',3);
define('ROLE_TRACKING',4);
define('ROLE_SUPER_VISOR',5);
define('ROLE_USER',2);
define('ROLE_CS',6);
define('DEFAULT_VENDOR',1);
define('APP_NAME',"VCONNECT_WEB");
define('APP_API',"VCONNECT_API");

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    protected  function getUserByCookie($cookieName)
    {

        $user= DB::table('users as a')
            ->leftJoin('customers as b','a.id','b.account_id')
            ->where('api_token', $cookieName)
            ->where('role',"!=",0)
            ->select('role','a.id','b.enterprise_number', 'a.name','a.email','b.companyname')
            ->first();

        return $user;


    }

    protected function addCustomerChangeStateLog($enterpriseNo, $cusId, $reason, $status, $userId)
    {
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$userId."|CHANGE_CUSTOMER_STATUS|".$enterpriseNo."|NEW_STATUS|".$status."|REASON|".$reason);

      $data=[
        "enterprise_number"=>$enterpriseNo,
        "cus_id"=>$cusId,
        "reason"=>$reason,
        "status"=>$status,
        "user_id"=>$userId
      ];
      DB::table("customers_log_states")
        ->insert($data);
      return true;

    }


  protected function SetActivity($jsonData, $table, $dataID, $rootid, $action, $description, $enterprise_number, $hotline_number)
  {
    $reason="";

    if(is_array($jsonData))
    {
      $reason= isset($jsonData['reason'])?"[R]".$jsonData['reason']:"";
    }



    $user= Auth::user()->id;
    $active = new Activity();
    $active->data_table= $table;
    $active->enterprise_number= $enterprise_number;
    $active->hotline_number= $hotline_number;
    $active->raw_log=json_encode($jsonData);
    $active->user_id=$user;
    $active->root_id= $rootid;
    $active->data_id= $dataID;
    $active->action= $action;
    $active->description=$description.$reason;
    $active->save();
  }

  protected function SetActivityBackup($jsonData, $table, $dataID, $rootid, $action, $description, $enterprise_number, $hotline_number)
  {
    $user= Auth::user()->id;

    $active = new ActivityBackup();
    $active->data_table= $table;
    $active->enterprise_number= $enterprise_number;
    $active->hotline_number= $hotline_number;
    $active->raw_log=json_encode($jsonData);
    $active->user_id=$user;
    $active->root_id= $rootid;
    $active->data_id= $dataID;
    $active->action= $action;
    $active->description=$description;
    $active->save();
  }

  protected function CDRActivity($profile,$cdr, $enterprise_number,$action)
  {
    // Find the primmary
//    $customer= Customers::where("enterprise_number", $enterprise_number)->whereIn('blocked',[0,1])->first();
    if($profile==config('server.server_profile'))
    {

      $CDR= new CDRActivity();

    }
    else
    {
      $CDR= new CDRActivityBackup();

    }

    $CDR->action= $profile."|".$action ;
    $user=Auth::user()->id;


    $CDR->enterprise_number= $enterprise_number;
    $CDR->cdr= $cdr;
    $CDR->user_id=$user;
    $CDR->save();
  }

  protected function ApiReturn($data, $status, $message, $code)
  {


    $return = (object)[];
    $return->status = $status;
    if ($data) {
      if ($status == false) {
        $return->errors = $data;
      } else {
        $return->data = $data;
      }
    }
    if ($message) {
      $return->message = $message;
    }

    return response()->json($return, $code ? $code : 200);
  }


  public function addSipRouting($sip, $backupState)
  {
    Log::info("SIP CONFIG GLOBAL FOR HOTLINE ".$sip->hotline);
    $arrAcl = ['ip_auth' => $sip->ip_auth,
      'ip_proxy' => $sip->ip_proxy?$sip->ip_proxy:null,
      'description' => $sip->description?$sip->description:null,
      'block_regex_caller' => '',
      'block_regex_callee' => config('sip.block_regex_callee'),
      'allow_regex_caller' => '',
      'allow_regex_callee' => config('sip.allow_regex_callee')
    ];

    $arrAclBackup = ['ip_auth' => $sip->ip_auth_backup?$sip->ip_auth_backup:null,
      'ip_proxy' => $sip->ip_proxy_backup?$sip->ip_proxy_backup:null,
      'description' => $sip->description?$sip->description:null,
      'block_regex_caller' => '',
      'block_regex_callee' => config('sip.block_regex_callee'),
      'allow_regex_caller' => '',
      'allow_regex_callee' => config('sip.allow_regex_callee')
    ];

      ////////// LƯU BÌNH THƯỜNG ==================================================================LƯU BÌNH THƯỜNG ==================================================================LƯU BÌNH THƯỜNG ==================================================================

      $acl = SBCRouting::where('caller',$sip->hotline)->where('i_customer',$sip->cus_id)
        ->select('i_acl as ACL','i_acl_backup')
        ->first();
      /** @var  ACL    $arrRoutingPrimary */
      if ($acl) {
          Log::info("Đã có ACL");
          $aclid = $acl->ACL;
          array_filter($arrAcl);

          SBCAcl::where('i_acl', $aclid)
              ->update($arrAcl);

          $aclidBackup = $acl->i_acl_backup;
          if ($sip->ip_auth_backup && $aclidBackup == $aclid) {
              $aclidBackup = SBCAcl::insertGetId($arrAclBackup);
              SBCRouting::whereRaw('caller=? and i_customer=?', [$sip->hotline, $sip->cus_id])
                  ->update('i_acl_backup', $aclidBackup);

          } else if ($sip->ip_auth_backup && $aclidBackup != $aclid) {
              SBCAcl::where('i_acl', $aclidBackup)
                  ->update($arrAclBackup);
          }

          $editMode = true;
      } else {

          $editMode = false;
          $aclid = SBCAcl::insertGetId($arrAcl);

          if ($sip->ip_auth_backup) {

              $aclidBackup = SBCAcl::insertGetId($arrAclBackup);
          } else
              $aclidBackup = $aclid;

      }

      if ($editMode == false) {
        $hotlineRoutingCaller = new SBCRouting();
        $hotlineRoutingCallee = new SBCRouting();

        $hotlineRoutingCaller->status = $sip->status;
        $hotlineRoutingCallee->status = $sip->isRunOnBackup ? 0 : $sip->status;
      } else {
        $hotlineRoutingCaller = SBCRouting::where("direction", 1)->where('i_customer', $sip->cus_id)->where('caller', $sip->hotline)->first();
        $hotlineRoutingCallee = SBCRouting::where("direction", 2)->where('i_customer', $sip->cus_id)->where('callee', $sip->hotline)->first();
        $hotlineRoutingCaller->status = $sip->isRunOnBackup ? 1 : $sip->status;
        $hotlineRoutingCallee->status = 0;
      }
      $hotlineRoutingCaller->direction=1;
      $hotlineRoutingCaller->auto_detect_blocking=isset($sip->auto_detect_blocking)?$sip->auto_detect_blocking:1;
      $hotlineRoutingCaller->caller=$sip->hotline;
      $hotlineRoutingCaller->i_acl= $aclid;
      $hotlineRoutingCaller->i_acl_backup= $aclidBackup;
      $hotlineRoutingCaller->destination= isset($sip->telco_destination)&&$sip->telco_destination?$sip->telco_destination:config('sip.RoutingDestination');
      $hotlineRoutingCaller->priority= 10;
      $hotlineRoutingCaller->i_vendor= 2;
      $hotlineRoutingCaller->i_customer= $sip->cus_id;
      $hotlineRoutingCaller->description= $sip->description;
      $hotlineRoutingCaller->network= 1;
      $hotlineRoutingCaller->i_sip_profile= 1;
      $hotlineRoutingCaller->save();

      $hotlineRoutingCallee->direction = 2;
      $hotlineRoutingCallee->callee = $sip->hotline;
      $hotlineRoutingCallee->i_acl = 1;
      $hotlineRoutingCallee->i_acl_backup = 2;
      $hotlineRoutingCallee->destination = $sip->destination;
      $hotlineRoutingCallee->priority = 10;
      $hotlineRoutingCallee->i_vendor = 0;
      $hotlineRoutingCallee->i_customer = $sip->cus_id;
      $hotlineRoutingCallee->description = $sip->description;
      $hotlineRoutingCallee->network = 2;
      $hotlineRoutingCallee->i_sip_profile = isset($sip->profile_id_backup)?$sip->profile_id_backup: config('sbc.profile_id_backup');

      $hotlineRoutingCallee->save();

      Hotlines::where('id', $sip->hotline_id)
        ->update(['sip_config' => date("Y-m-d H:i:s"), 'vendor_id'=>$sip->vendor->i_vendor]);

    return response()->json(['status' => true], 200);

  }

  protected function GetServerPrimary($current) {
    if (config("server.server_profile") == $current) // Kiểm tra server nhận lệnh có trùng với server đang cấu hình
    {
      return true;
    } else {
      return false;
    }
  }

  function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  /**
   * @param $service_id
   * @param $isBackup
   * @return int
   */
 protected function CheckQuantityService($service_id, $isBackup)
{
  // DISABLE CHECK QUANTIY SERVICE  HOTFIX20210315
  return [];
}

  /**
   * @param $mode = 0, thêm mới khách, $mode=1 --> Chỉ update
   * @param $productCode
   * @param $customer
   * @param $BACKUP_STATE
   * @return bool
   *
   *
   */
protected function UpdateSubCharge($mode, $productCode,$customer, $BACKUP_STATE)
{
  // DISABLE SUBCHARGE// DISABLE CHECK QUANTIY SERVICE  HOTFIX20210315
  return true;

}

protected  function CheckChargingHotline($enterprise, $BACKUP_STATE)
{


  return -1; // Khong tim thay goi cuoc Ko cho tao

}
  protected function SendChargingHotline($enterprise, $line, $BACKUP_STATE) {
    // Disable charging hotline // DISABLE  SERVICE  HOTFIX20210315
     return -2;
         // Tìm và cộng tiền
  }


  function removeZero($data)
  {
    if ($data[0] == 0) {
      return substr($data, 1);
    } else {
      return $data;
    }
  }


  protected function getEntity($user_id) {
    $entityObject = (object)[];

    $lstEntity = DB::select("select e.entity_key from entity e join role_entity r 
on r.entity_id= e.id
join users u on r.role_id= u.role
 where u.id=?", [$user_id]);
    if (count($lstEntity) > 0) {
      foreach ($lstEntity as $item) {
        $entityObject->{$item->entity_key} = true;
      }

      return $entityObject;
    } else {
      return false;
    }
  }

  protected function checkEntity($user_id, $entity) {
    $lstEntity = $this->getEntity($user_id);

    if (isset($lstEntity->{$entity}) && $lstEntity->{$entity}) {
      return true;
    } else {
      return false;
    }
  }

  protected function setEntity($role_key, $entity_key)
  {
    $roleID= Roles::where("role_key",$role_key)->first();
    $entity= Entity::where("entity_key",$entity_key)->first();
    if($roleID && $entity)
    {
      $res= new RoleEntity();
      $res->role_id= $roleID->id;
      $res->entity_id= $entity->id;
      $res->save();

    }
  }

}
