<?php

  namespace App\Http\Controllers;

  use App\ChargeLog;
  use App\Customers;
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Log;
  use Validator;

  class HomePhoneChargingController extends Controller
  {
    //

    public function postRequestCheckCharging(Request $request) {
      $user = $request->user();
      $startTime = round(microtime(true) * 1000);
      if (!$this->checkEntity($user->id, "CHARGING_MANUAL")) {
        Log::info($user->email . '  TRY TO GET HomephoneChargingController.postRequestCheckCharging WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|POSTREQUESTCHECKCHARGING|START|POSTREQUESTCHECKCHARGING");

      $validData = $request->only('data');
      $validator = Validator::make($validData, ['data' => 'required|array'

      ]);
      if ($validator->fails()) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|POSTREQUESTCHECKCHARGING|" . $logDuration . "|POSTREQUESTCHECKCHARGING Invalid input data");
        /** @var LOG $logDuration */

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }

      $lstEnterprise = $request->data;

      $checkingEnterprise = (object)[];
      $lstCheckedEnterprise = [];

      foreach ($lstEnterprise as $item) {
        if (isset($checkingEnterprise->{$item['enterprise_number']}) && $checkingEnterprise->{$item['enterprise_number']}) {
          // DO NO THING

        } else {
          if ($customer = Customers::where("enterprise_number", $item['enterprise_number'])->whereIn('blocked', [0, 1])->first()) {
            $checkingEnterprise->{$item['enterprise_number']} = true;
            $zeroEnterprise = $this->removeZero($item['enterprise_number']);

            $currentVconnectCharge = DB::select("Select b.total_amount, a.enterprise_number, a.id cus_id  FROM customers a
                      JOIN 
                    (
                    SELECT SUM(chotSale) AS total_amount, enterprise_number
                    FROM (
                    SELECT SUM(total_amount) chotSale, a.enterprise_number
                    FROM call_fee_cycle_status a
                    WHERE cycle_to > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00') and enterprise_number=?
                    GROUP BY a.enterprise_number UNION ALL
                    SELECT SUM(total_amount) chotSale, a.enterprise_number
                    FROM subcharge_fee_cycle_status a
                    WHERE cycle_to > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')  and enterprise_number=?
                    GROUP BY a.enterprise_number UNION ALL
                    SELECT SUM(total_amount) chotSale, a.enterprise_number
                    FROM sms_fee_cycle_status a
                    WHERE cycle_to > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')  and enterprise_number=?
                    GROUP BY a.enterprise_number
                    
                    ) b
                    GROUP BY enterprise_number
                    )
                    b ON SUBSTR(a.enterprise_number,2) = b.enterprise_number  
                    
                    where a.id= ?", [$zeroEnterprise, $zeroEnterprise, $zeroEnterprise, $customer->id]);

            if (count($currentVconnectCharge) > 0) {
              // Là khách hàng
              $customerCharge = $currentVconnectCharge[0];
              $customerCharge->bccs_amount = (int)$item['value'];
              if ($customerCharge->total_amount > ($item["value"] * 1.1)) {
                // Có giá trị cần charge
                $customerCharge->amount_to_charge = intval($customerCharge->total_amount - ($item["value"] * 1.1));
                array_push($lstCheckedEnterprise, $customerCharge);
              }
            }
          }
        }
      }

      return $this->ApiReturn($lstCheckedEnterprise, true, null, 200);
    }

    public function postRequestCharge(Request $request) {
      $limitAmount=config("sbc.limit_charge_amount");
      $startTime = round(microtime(true) * 1000);
      $user = $request->user;
      if ($user->role != ROLE_ADMIN && $user->role != ROLE_USER && $user->role != ROLE_CS) {
        return ['error' => 'Permission denied'];
      }

      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|POSTREQUESTCHARGE|START|POSTREQUESTCHARGE");

      $validData = $request->only('data');
      $validator = Validator::make($validData, ['data' => 'required|array'

      ]);
      if ($validator->fails()) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|POSTREQUESTCHARGE|" . $logDuration . "|POSTREQUESTCHARGE Invalid input data");
        /** @var LOG $logDuration */

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }

      $listCustomer = $request->data;
      $dataAfterCharge= [];

      if (count($listCustomer) > 0) {
        foreach ($listCustomer as $item) {


          $amountToCharge= $item['amount_to_charge'];
          $i=0;
          while ($amountToCharge> $limitAmount)
          {
            $i++;

            $charging= $this->sendToChargeLog($i, ($item['enterprise_number']), $limitAmount, $item['cus_id']);


            $amountToCharge= $amountToCharge-$limitAmount;

            array_push($dataAfterCharge, $charging);
          }

          $charging= $this->sendToChargeLog(-1, ($item['enterprise_number']), $amountToCharge, $item['cus_id']);
          array_push($dataAfterCharge, $charging);

          //          Log::info($amountToCharge>3000?3000:$amountToCharge);


        }

        }

      return $this->ApiReturn($dataAfterCharge, true, $this->generateRandomString(8).date("ymdHis"), 200);
    }


    private  function sendToChargeLog($i,$enterpriseNumber,$amount,$cus_id)
    {

      $zeroEnterprise=$this->removeZero($enterpriseNumber);
      $eventId= "000001.".date("YmdHis")."-".$this->generateRandomString(8). $zeroEnterprise."-".$i;
      $description='000001|GPDN|VCONNECT|'.$zeroEnterprise.'|QUATITY MONTHLY REMAIN CHARGE|'.$zeroEnterprise.'|'.date("YmdHis").'|'.$amount;



      $charging= new ChargeLog();
      $charging->event_type="000001";
      $charging->event_source="3";
      $charging->event_id=$eventId;
      $charging->charge_session_id=$eventId;
      $charging->display_num="";
      $charging->called_num="";
      $charging->hotline_num="";
      $charging->enterprise_num=$zeroEnterprise;
      $charging->event_occur_time=date("Y-m-d H:i:s");
      $charging->charge_time=date("Y-m-d H:i:s");
      $charging->amount=$amount;
      $charging->count=0;
      $charging->total_count=0;
      $charging->total_amount=0;
      $charging->charge_status=0;
      $charging->charge_result="";
      $charging->charge_description="";
      $charging->description=$description;
      $charging->direction_type="";
      $charging->destination_type="";
      $charging->account_id=$cus_id;
      $charging->retry_times=0;
      $charging->insert_time=date("Y-m-d H:i:s");
      $charging->retry_after=date("Y-m-d H:i:s");
      $charging->cus_id=-9999;
      $charging->save();

      $this->SetActivity($description,"charge_log",$charging->id,0,config("sbc.action.recharge"),"Gửi charge cước homephone  số tiền ". $amount,$enterpriseNumber, null);


      return $charging;




    }
  }
