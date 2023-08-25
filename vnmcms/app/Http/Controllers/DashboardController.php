<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    //
    public function getHomePage(Request $request)
    {

        $cookie=null;
        $user= null;
        if (isset($_COOKIE["sbc"])) {
            $cookie = $_COOKIE["sbc"];
            $user = $this->getUserByCookie($cookie);

        }
        else
        {
//            return "NO THING";
        }

        return view('dashboard', ['sitename'=>'#', 'lang'=>'vi','user'=>$user]);



    }

    public function getDashboardInfo(Request $request)
    {
       $user= $request->user();

      if (!$this->checkEntity($user->id, "VIEW_DASHBOARD")) {
        Log::info($user->email . '  TRY TO GET DashboardController.getDashboardInfo WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


        $dashboard = (object)[];
        $dashboard->customer = (object)[];
        $dashboard->hotline = (object)[];
        $dashboard->call = (object)[];
        $dashboard->amount = (object)[];


        $info = DB::select("select 'customer' as col, sum(1) as total,
                 IFNULL(SUM(CASE WHEN blocked=0 THEN 1 ELSE 0 END), 0) col1,
                 IFNULL(SUM(CASE WHEN blocked=1 THEN 1 ELSE 0 END), 0) col2,
                  IFNULL(SUM(CASE WHEN blocked=2 THEN 1 ELSE 0 END), 0) col3 
                 from customers
                
                union all 
                
                select 'hotline' as col, sum(1) as total,
                 IFNULL(SUM(CASE WHEN status=0 THEN 1 ELSE 0 END), 0) col1,
                 IFNULL(SUM(CASE WHEN status=1 THEN 1 ELSE 0 END), 0) col2,
                  IFNULL(SUM(CASE WHEN status=2 THEN 1 ELSE 0 END), 0) col3 
                 from hot_line_config
                
                union all 
                
              select 'quantity' as col, 0 as total ,
0 as col1,
0 as col2,
0 as col3
                ");
        $dash = [];
        if (count($info) == 3) {
            foreach ($info as $val) {
                $dash[$val->col] = $val;
            }
        }


        $dashboard->customer->all = $dash['customer']->total;
        $dashboard->customer->active = $dash['customer']->col1;
        $dashboard->customer->inactive = $dash['customer']->col2;
        $dashboard->customer->cancel =$dash['customer']->col3;
        $dashboard->hotline->all = $dash['hotline']->total;
        $dashboard->hotline->active = $dash['hotline']->col1;
        $dashboard->hotline->inactive = $dash['hotline']->col2;
        $dashboard->hotline->cancel = $dash['hotline']->col3;
        $dashboard->call->success =$dash['quantity']->col1;
        $dashboard->call->duration = $dash['quantity']->col3;
        $dashboard->call->failed = $dash['quantity']->col2;
        $dashboard->call->all =  $dash['quantity']->total;

        $dashboard->amount->call = DB::select("SELECT sum(total_amount) total_amount FROM call_fee_cycle_status where cycle_from >= CAST(DATE_FORMAT(NOW() ,'%Y-%m-01') as DATE)")[0]->total_amount;
        $dashboard->amount->sub = DB::select("SELECT sum(total_amount) total_amount FROM subcharge_fee_cycle_status where cycle_from >= CAST(DATE_FORMAT(NOW() ,'%Y-%m-01') as DATE)")[0]->total_amount;
        $dashboard->amount->sms =0;
        $dashboard->amount->all =intval( $dashboard->amount->call)+intval( $dashboard->amount->sub);

//        $dashboard->amount->call =$dash['amount']->col1;
//        $dashboard->amount->sub = $dash['amount']->col2;
//        $dashboard->amount->sms =$dash['amount']->col3;
//        $dashboard->amount->all =$dash['amount']->total;
//
//
      //  $dashboard->user =$request->user;
        return response()->json($dashboard);

    }

    public function postViewDashboardDailyFlow(Request $request)
    {
        $user= $request->user;
      if (!$this->checkEntity($user->id, "VIEW_DASHBOARD")) {
        Log::info($user->email . '  TRY TO GET DashboardController.postViewDashboardDailyFlow WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }

        $datePeriod = (object)[];
        $datePeriod->start = (date("Y-m-d 00:00:00"));
        $datePeriod->end = date("Y-m-d H:i:s");

        $querySuccess = "SELECT HOUR(setup_time) AS hour, 
                  IFNULL(SUM(duration), 0) total_minute, IFNULL(SUM(CASE WHEN duration > 0 THEN 1 ELSE 0 END), 0) num_of_call 
                    FROM sbc.cdr_vendors   
                    WHERE setup_time BETWEEN ? AND ? AND i_vendor IN (2,17)
                    GROUP BY HOUR(setup_time)";

            $queryFailed = "SELECT HOUR(setup_time) AS hour, IFNULL(SUM(0), 0) total_minute, IFNULL(SUM(1), 0) num_of_call             
            FROM sbc.cdr_vendors_failed  
            WHERE (setup_time BETWEEN ? AND ?) AND i_vendor IN (2,17)
            GROUP BY HOUR(setup_time)
            ";


        $resCallFailed = DB::select($queryFailed, [$datePeriod->start, $datePeriod->end]);
        $resCallSuccess = DB::select($querySuccess, [$datePeriod->start, $datePeriod->end]);



        $resHour = DB::table('report_hour_of_day')
            ->select('hour')
            ->groupBy('hour')
            ->get();

        $resOverCharge= DB::select( "Select count(*) as total_overcharge from (SELECT MAX(charge_time) AS time, a.enterprise_num, a.charge_result
FROM charge_log a
WHERE insert_time > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')  and charge_time > DATE_FORMAT(NOW(),'%Y-%m-01 00:00:00')  and a.charge_result  <>'0' and   a.charge_result <>''
GROUP BY enterprise_num) a " );



        $callFailed = array();
        $lstHour = array();
        $callSuccessAmount = array();
        $callSuccessTime = array();
        $total_success_call = 0;
        $total_success_call_time = 0;
        $total_failed_call = 0;
        foreach ($resCallSuccess as $key => $val) {
            $total_success_call += intval($val->num_of_call);
            $total_success_call_time += intval($val->total_minute);
            //    array_push($callSuccessAmount, intval($val->num_of_call));
            array_push($callSuccessTime, ceil(intval($val->total_minute) / 60));
        }
        foreach ($resCallFailed as $key => $val) {
            $total_failed_call += intval($val->num_of_call);
            array_push($callFailed, intval($val->num_of_call));
            //  array_push($callSuccessTime, intval($val->total_minute));
        }
        foreach ($resHour as $key => $val) {
            array_push($callSuccessAmount, intval($val->hour));
            array_push($lstHour, intval($val->hour));
            //  array_push($callSuccessTime, intval($val->total_minute));
        }
        return response()->json([
            'total' => ['success' => $total_success_call,
                'success_time' => $total_success_call_time,
                'failed' => $total_failed_call],
            'call_success' => $resCallSuccess,
            'call_time' => $callSuccessTime,
            'call_failed' => $resCallFailed,
            'date' => $datePeriod,
            'hour' => $lstHour,
            'overcharge'=>$resOverCharge[0]->total_overcharge

        ]);
    }
}
