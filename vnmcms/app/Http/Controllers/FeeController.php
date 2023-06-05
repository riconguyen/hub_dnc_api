<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class FeeController extends Controller
{
    //


    public function getCallFeeCycleByEntNumber($id)
    {
        $res= DB::table('call_fee_cycle_status')
            ->where('enterprise_number', 'like', '%'.$id)
            ->select('*',DB::raw("sum(total_amount) as sum_call"))
            ->groupBy('id')
            ->get();

        return $res;

    }
    public function getSmsFeeCycleByEntNumber($id)
    {
        $res= DB::table('sms_fee_cycle_status')
            ->where('enterprise_number', 'like', '%'.$id)
            ->select('*',DB::raw("sum(total_amount) as sum_sms"))
            ->groupBy('id')
            ->get();

        return $res;

    }
    public function getSubchargeFeeCycleByEntNumber($id)
    {

        $res= DB::table('subcharge_fee_cycle_status')
            ->where('enterprise_number', 'like', '%'.$id)
            ->select('*',DB::raw("sum(total_amount) as sum_sub"))
            ->groupBy('id')
            ->get();

        return $res;

    }


    public function getFeeByEntNumber(Request $request, $id)
    {
        $user= $request->user;

      $startTime = round(microtime(true) * 1000);
      if (!$this->checkEntity($user->id, "VIEW_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET FeeController.getFeeByEntNumber WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


      $call = $this->getCallFeeCycleByEntNumber($id);
        $sms = $this->getSmsFeeCycleByEntNumber($id);
        $sub = $this->getSubchargeFeeCycleByEntNumber($id);

        return response()->json(['call'=>$call, 'sub'=>$sub, 'sms'=>$sms]);
    }
}
