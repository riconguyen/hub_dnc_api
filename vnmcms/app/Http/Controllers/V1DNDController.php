<?php

namespace App\Http\Controllers;

use App\DNC;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Validator;

class V1DNDController extends Controller
{
    //

    public function postDnd(){
        $msisdn= request('msisdn',null);
        $telco= request('telco',null);
        $shortcode= request('shortcode',null);
        $info= request('info',null);
        $mo_time = request('mo_time',date('Y-m-d H:i:s'));
        $cmd_code  = request('cmd_code',null);

        $mo_time= date_format(new DateTime($mo_time), 'd/m/Y H:i:s');

        $validator = Validator::make(request()->all(),
            [
                'msisdn' => 'required|max:50',
                'telco' => 'required|max:10',
                'shortcode' => 'required|max:10',
                'info' => 'required|max:250',
                'mo_time' => 'required|max:25',
                'cmd_code' =>  [
                    'required',
                    Rule::in(['DK', 'HUY']),
                ],
            ]
        );
        if ($validator->fails()) {
            return response()->json(['error_code'=>1, 'error_desc'=>$validator->errors()],400);
        }


        DB::beginTransaction();
        try {
            $dnc= DNC::where('msisdn',$msisdn)->first();
            if(!$dnc)
            {
                $dnc= new DNC();
                $dnc->msisdn= $msisdn;
            }

            $dnc->telco=$telco;
            $dnc->shortcode= $shortcode;
            $dnc->info=$info;
            $dnc->mo_time= $mo_time;
            $dnc->cmd_code= $cmd_code;
            $dnc->save();
            DB::commit();
        }
        catch (\Exception $exception)
        {
            Log::info($exception->getMessage());
            Log::info($exception->getTraceAsString());
            DB::rollback();
            return response()->json(['error_code'=>1, 'error_desc'=>'Internal server error'],500);
        }
        return response()->json(['error_code'=>0 ],200);
     }
}
