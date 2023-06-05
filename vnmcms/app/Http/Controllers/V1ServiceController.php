<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

class V1ServiceController extends Controller
{
    //





    public function getServices(Request $request)
    {
        $user= $request->user;

      if (!$this->checkEntity($user->id, "VIEW_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET V1ServiceController.getServices WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


      $page = 0;
        $take = 100;
        $errors = Validator::make($request->only('query','page', 'take'), [
                'query' => 'sometimes|unicode_valid|max:50',
                'page' => 'sometimes|integer|min:0|max:10',
                'take'=>'nullable|integer|min:0|max:1000',

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


        $countServices = DB::table('service_config')
          ->whereRaw('service_name like? ',['%'.$query.'%'])
            ->count();
        $res = DB::table('service_config')
            ->whereRaw('service_name like? ',['%'.$query.'%'])
            ->select("service_name", "product_code","updated_at", "status","type")
            ->take($take)
            ->skip($skip)
            ->get();


        return response()->json(['status'=>true, 'data'=>$res, 'count'=>$countServices,'page'=>1]);
    }


    public function getServiceByCode(Request $request)
    {
        $user= $request->user;
      if (!$this->checkEntity($user->id, "VIEW_SERVICE_CONFIG")) {
        Log::info($user->email . '  TRY TO GET V1ServiceController.getServiceByCode WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


        $errors = Validator::make($request->only('product_code'), [
                'product_code' => 'required|alpha_dash|max:50|exists:service_config',
            ]
        );
        if ($errors->fails()) {
            return $this->ApiReturn($errors->errors(), false, "The given data was invalid", 422);
        }

        $service=DB::table('service_config')
            ->where('product_code', $request->product_code)
            ->whereIn('status', [0,1])
            ->select('id', 'service_name', 'status','product_code', 'updated_at','ocs_charge')

            ->first();


        $id= $service->id;
        $service_config_price = DB::table('service_config_price')
            ->where("service_config_id", $id)
            ->whereIn('status',[0,1])
            ->get();
        $service_config_hotline_price = DB::table('service_config_hotline_price')
            ->where('service_config_id', $id)
            ->whereIn('status',[0,1])
            ->get();
        $service_option_price = DB::table('service_option_price')
            ->where('service_config_id', $id)
            ->whereIn('status',[0,1])
            ->get();
        $service_call_price = DB::table('call_fee_config')
            ->where('service_config_id', $id)
            ->whereIn('status',[0,1])
            ->get();
        $service_sms_price = DB::table('sms_fee_config')
            ->where('service_config_id', $id)
            ->whereIn('status',[0,1])
            ->get();
        $service_quantity_price = DB::table('quantity_config')
            ->where('service_config_id', $id)
            ->whereIn('status',[0,1])
            ->get();
        unset($service->id);
        $servicePacket= array('service' => $service, 'config_price' => $service_config_price,
            'hotline_price' => $service_config_hotline_price,
            'option_price' => $service_option_price,
            'call_price' => $service_call_price,
            'sms_price' => $service_sms_price,
            'quantity_price'=>$service_quantity_price
        );

        return $this->ApiReturn($servicePacket, true, null, 200);

    }


}
