<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
//postViewReportConnectRating
    private function filterDateRange($r)
    {
        $range = $r['datePeriod'];
        if (!isset($r['end_date'])) {
            $end_date = date('Y-m-d H:i:s');
        } else {
            $end_date = $r['end_date'];
        }
        switch ($range) {
            case 'day':
                $dateResult = (object)['start_date' => date('Y-m-d 00:00:00'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'week':
                $day = date_create(date('Y-m-d H:i:s'));
                date_modify($day, "-1 week");
                $dateResult = (object)['start_date' => date_format($day, 'Y-m-d H:i:s'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'month':
                $day = date_create(date('Y-m-d H:i:s'));
                date_modify($day, "-1 month");
                $dateResult = (object)['start_date' => date_format($day, 'Y-m-d H:i:s'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'quarter':
                $day = date_create(date('Y-m-d H:i:s'));
                date_modify($day, "-3 months");
                $dateResult = (object)['start_date' => date_format($day, 'Y-m-d H:i:s'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'year':
                $day = date_create(date('Y-m-d'));
                date_modify($day, "-1 year");
                $dateResult = (object)['start_date' => date_format($day, 'Y-m-d H:i:s'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'y2d':
                $dateResult = (object)['start_date' => date('Y-01-01 00:00:00'), 'end_date' => date("Y-m-d H:i:s")];
                break;
            case 'manual':
                $dateResult = (object)['start_date' => $r['start_date'], 'end_date' => $end_date];
                break;
        }
        return $dateResult;
    }

    private function renderDateRange($r)
    {
        $datePeriod = $this->filterDateRange($r);
        $startDate = new \DateTime($datePeriod->start_date);
        $endDate = new \DateTime($datePeriod->end_date);
        for ($i = $startDate; $i <= $endDate; $i->modify('+1 day')) {
            $dateRange[] = date_format($i, "Y-m-d");
        }
        return ($dateRange);
    }

    public function postViewReportQuantity(Request $request)
    {
        $user= $request->user;
      if (!$this->checkEntity($user->id, "VIEW_REPORT_QUANTITY")) {
        Log::info($user->email . '  TRY TO GET ReportController.postViewReportQuantity WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }


        $validatedData = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'report' => 'required',
            'datePeriod' => 'required',
        ]);
        $range = $request->all();
        $datePeriod = $this->filterDateRange($range);
        $result = DB::select("SELECT SUM(amount) TOTAL,
                IFNULL(SUM(CASE WHEN event_type='000002' THEN amount ELSE 0 END), 0) VOICE_CALL,
                IFNULL(SUM(CASE WHEN event_type='000002' THEN count ELSE 0 END), 0) VOICE_DURATION,
                IFNULL(SUM(CASE WHEN event_type='000001' THEN amount ELSE 0 END), 0) SUB,
                IFNULL(SUM(CASE WHEN event_type='000003' THEN amount ELSE 0 END), 0) SMS,
                IFNULL(SUM(CASE WHEN event_type='000003' THEN count ELSE 0 END), 0) SMS_DURATION
                from charge_log a
                where   insert_time  between ? and ?  and   event_occur_time between ? and ? ", [$datePeriod->start_date, $datePeriod->end_date,$datePeriod->start_date, $datePeriod->end_date]);




        $call_fee = $result[0]->VOICE_CALL;
        $call_duration = ceil($result[0]->VOICE_DURATION / 60);
        $sms_fee = $result[0]->SMS;
        $sms_duration = $result[0]->SMS_DURATION;
        $sub_fee = $result[0]->SUB;
        $total_fee = intval($result[0]->TOTAL);
        if ($total_fee > 0) {
            $sub_fee_per = ($sub_fee * 100) / $total_fee;
            $sms_fee_per = ($sms_fee * 100) / $total_fee;
            $call_fee_per = ($call_fee * 100) / $total_fee;
        } else {
            $sub_fee_per = 0;
            $sms_fee_per = 0;
            $call_fee_per = 0;
        }
        // select sum(total_amount) from call_fee_cycle_status where cycle_from >=@dateStart and cycle_to <=@endDate
        return response()->json(['fee' =>
                [array('name' => 'CALL_FEE', 'amount' => intval($call_fee),
                    'count' => $call_duration,
                    'unit' => 'min',
                    'percent' => $call_fee_per),
                    array('name' => 'SMS_FEE',
                        'amount' => intval($sms_fee),
                        'count' => $sms_duration,
                        'unit' => 'sms', 'percent' => $sms_fee_per),
                    array('name' => 'SUB_FEE',
                        'amount' => intval($sub_fee),
                        'count' => 0,
                        'unit' => "null",
                        'percent' => $sub_fee_per)
                ],
                'date' => [
                    'start_date' => date('d/m/Y', strtotime($datePeriod->start_date)),
                    'end_date' => date('d/m/Y', strtotime($datePeriod->end_date))],
                'total_fee' => $total_fee
            ]
        );
        //postViewReportQuantity
    }

    public function postViewReportQuantityOLD(Request $request)
    {
        $user= $request->user;
        if($user->role != ROLE_ADMIN )
        {
            return ['error'=>'Permission denied'];
        }




        $validatedData = $request->validate([
            'start_date'=>'nullable|date',
            'end_date'=>'nullable|date',
            'report'=>'required',
            'datePeriod'=>'required',
        ]);






        $range = $request->all();
        $datePeriod = $this->filterDateRange($range);




        $call_fee = DB::table('charge_log')
            ->whereBetween('event_occur_time', [ $datePeriod->start_date, $datePeriod->end_date])
            ->whereBetween('insert_time', [ $datePeriod->start_date, $datePeriod->end_date])
            ->where('event_type','000002')
            ->select(DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(count) as total_duration'))
            ->get();

        $sms_fee = DB::table('charge_log')
            ->whereBetween('event_occur_time', [ $datePeriod->start_date, $datePeriod->end_date])
            ->whereBetween('insert_time', [ $datePeriod->start_date, $datePeriod->end_date])
            ->where('event_type','000003')
            ->select(DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(count) as total_duration'))
            ->get();

        $sub_fee = DB::table('charge_log')
            ->whereBetween('event_occur_time', [ $datePeriod->start_date, $datePeriod->end_date])
            ->whereBetween('insert_time', [ $datePeriod->start_date, $datePeriod->end_date])
            ->where('event_type','000001')
            ->sum('amount');

        //  return response()->json($sub_fee);

//
//        // Cấu hình dữ liệu báo cáo
//        $call_fee = DB::table('call_fee_cycle_status')
//            ->whereDate('cycle_from', '>=', $datePeriod->start_date)
//            ->whereDate('cycle_to', '<=', $datePeriod->end_date)
//            ->select(DB::raw('SUM(total_amount) as total_amount'), DB::raw('SUM(total_duration) as total_duration'))
//            ->get();
//        $sms_fee = DB::table('sms_fee_cycle_status')
//            ->whereDate('cycle_from', '>=', $datePeriod->start_date)
//            ->whereDate('cycle_to', '<=', $datePeriod->end_date)
//            ->select(DB::raw('SUM(total_amount) as total_amount'), DB::raw('SUM(total_count) as total_count'))
//            ->get();
//        $sub_fee = DB::table('subcharge_fee_cycle_status')
//            ->whereDate('cycle_from', '>=', $datePeriod->start_date)
//            ->whereDate('cycle_to', '<=', $datePeriod->end_date)
//            ->sum('total_amount');
//
//
//


        $total_fee = $sms_fee[0]->total_amount + $call_fee[0]->total_amount + $sub_fee;
        if ($total_fee > 0) {
            $sub_fee_per = ($sub_fee * 100) / $total_fee;
            $sms_fee_per = ($sms_fee[0]->total_amount * 100) / $total_fee;
            $call_fee_per = ($call_fee[0]->total_amount * 100) / $total_fee;
        } else {
            $sub_fee_per = 0;
            $sms_fee_per = 0;
            $call_fee_per = 0;
        }
        // select sum(total_amount) from call_fee_cycle_status where cycle_from >=@dateStart and cycle_to <=@endDate
        return response()->json(['fee' =>
                [array('name' => 'CALL_FEE', 'amount' => $call_fee[0]->total_amount, 'count' => ceil($call_fee[0]->total_duration/60), 'unit' => 'min', 'percent' => $call_fee_per),
                    array('name' => 'SMS_FEE', 'amount' => $sms_fee[0]->total_amount, 'count' => $sms_fee[0]->total_duration, 'unit' => 'sms', 'percent' => $sms_fee_per),
                    array('name' => 'SUB_FEE', 'amount' => $sub_fee, 'percent' => $sub_fee_per)
                ],
                'date' => ['start_date'=>date('d/m/Y',strtotime($datePeriod->start_date)),'end_date'=>date('d/m/Y',strtotime($datePeriod->end_date))],
                'total_fee' => $total_fee
            ]
        );
        //postViewReportQuantity
    }

  public function postViewReportFlow(Request $request) {
    $user = $request->user;
    if (!$this->checkEntity($user->id, "VIEW_REPORT_FLOW")) {
      Log::info($user->email . '  TRY TO GET ReportController.postViewReportFlow WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validatedData = $request->validate(['start_date' => 'nullable|date', 'end_date' => 'nullable|date', 'report' => 'required', 'datePeriod' => 'required',
        ]);
        $range = $request->all();
        $datePeriod = $this->filterDateRange($range);
        // So luong cuoc goi
        $querySuccess = "select DATE(a.full_time) as day, IFNULL(SUM(duration),0) total_minute, 
                COUNT(b.id) as num_of_call  from report_days  a 
                left join sbc.cdr_vendors b on a.full_time= DATE(b.setup_time)   and i_vendor in ( 2,17) 
                where  full_time between ? and ?   group by day ";
        $resCallSuccess = DB::select($querySuccess, [$datePeriod->start_date, $datePeriod->end_date]);
        $queryFailed = "select DATE(a.full_time) as day, COUNT(b.id) as num_of_call  
from report_days  a left join sbc.cdr_vendors_failed b on a.full_time= DATE(b.setup_time)   and i_vendor in ( 2,17) 
where  full_time between ? and ? group by day";
        $resCallFailed = DB::select($queryFailed, [$datePeriod->start_date, $datePeriod->end_date]);
        $callFailed = array();
        $dateAvail = array();
        $callSuccessAmount = array();
        $callSuccessTime = array();
        $total_success_call = 0;
        $total_success_call_time = 0;
        $total_failed_call = 0;
        foreach ($resCallSuccess as $key => $val) {
            $total_success_call += intval($val->num_of_call);
            $total_success_call_time += ceil(intval($val->total_minute) / 60);
            array_push($callSuccessAmount, intval($val->num_of_call)?intval($val->num_of_call):0);
            array_push($callSuccessTime, ceil(intval($val->total_minute) / 60));
        }
        foreach ($resCallFailed as $key => $val) {
            $total_failed_call += intval($val->num_of_call);
            array_push($callFailed, intval($val->num_of_call)?intval($val->num_of_call):0);
            array_push($dateAvail, ($val->day));
            //  array_push($callSuccessTime, intval($val->total_minute));
        }
        return response()->json([
            'total' => ['success' => $total_success_call, 'success_time' => $total_success_call_time, 'failed' => $total_failed_call],
            'call_success' => $callSuccessAmount, 'call_time' => $callSuccessTime, 'call_failed' => $callFailed, 'date' =>
                ['start_date' => date('d/m/Y', strtotime($datePeriod->start_date)), 'end_date' => date('d/m/Y', strtotime($datePeriod->end_date))],
            'date_range' => $dateAvail
        ]);
        //postViewReportFlow
    }

    public function postViewReportCustomer(Request $request)
    {
        $user= $request->user;
      if (!$this->checkEntity($user->id, "VIEW_REPORT_CUSTOMER")) {
        Log::info($user->email . '  TRY TO GET ReportController.postViewReportCustomer WITHOUT PERMISSION');
        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
      }



        $validatedData = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'report' => 'required',
            'datePeriod' => 'required',
        ]);
        //postViewReportConnectRating
        $range = $request->all();
        // Cấu hình dữ liệu báo cáo
        $datePeriod = $this->filterDateRange($range);
        $queryTotalCustomer = "select DATE(a.full_time) as day, COUNT(b.id) as number_of_cus  
        from report_days  a left join customers b on a.full_time= DATE(b.created_at)  
        where full_time between ? and ?   group by day";
        $resCustomer = DB::select($queryTotalCustomer, [$datePeriod->start_date, $datePeriod->end_date]);
        $queryHotline = "select DATE(a.full_time) as day, COUNT(b.id) as number_of_hotline 
        from report_days  a left join hot_line_config b on a.full_time= DATE(b.created_at)  
        where full_time between ? and ?  group by day";
        $resHotline = DB::select($queryHotline, [$datePeriod->start_date, $datePeriod->end_date]);
        $customer = array();
        $dateAvail = array();
        $totalCustomer = 0;
        $inActiveCustomer = array();
        $totalInActiveCustomer = 0;
        $hotline = array();
        $totalHotline = 0;
        foreach ($resCustomer as $key => $val) {
            $totalCustomer += intval($val->number_of_cus);
            array_push($customer, intval($val->number_of_cus));
            array_push($dateAvail, ($val->day));
            //  array_push($callSuccessTime, intval($val->total_minute));
        }
        foreach ($resHotline as $key => $val) {
            $totalHotline += intval($val->number_of_hotline);
            array_push($hotline, intval($val->number_of_hotline));
        }
        return response()->json(['date' => ['start_date' => date('d/m/Y', strtotime($datePeriod->start_date)), 'end_date' => date('d/m/Y', strtotime($datePeriod->end_date))],
            'customer' => $customer,
            'hotline' => $hotline,
            'total' => [
                'customer' => $totalCustomer,
                'hotline' => $totalHotline
            ],
            'date_range' => $dateAvail
        ]);
    }

  public function postViewReportMonthlyAudit(Request $request) {
    $user = $request->user;

    if (!$this->checkEntity($user->id, "VIEW_REPORT_MONTHLY_DESTINATION")) {
      Log::info($user->email . '  TRY TO GET ReportController.postViewReportMonthlyAudit WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission denied"], 403);
    }

    $validatedData = $request->validate(['start_date' => 'required|date', 'count' => 'sometimes|numeric|max:100', 'page' => 'sometimes|numeric|max:1000'

    ]);

    $start_date = date("Y-m-01 00:00:00", strtotime($request->start_date));
    $end_date = date("Y-m-t 23:59:59", strtotime($start_date));

    $returnReport = new \stdClass();

    $sqlGroupPrefix = DB::select("select prefix_group, GROUP_CONCAT(prefix_type_id SEPARATOR ',') listId  from prefix_type_name f group by f.prefix_group");

    $lstGroupPrefix=DB::table("prefix_type_group")->get();
    $groupObject=new \stdClass();
    foreach($lstGroupPrefix as $groupPrefix)
    {
  $groupObject->{$groupPrefix->id}= $groupPrefix->group_name;
    }

    $groupObject->{0}="No group";
    $groupObject->sub="Sub";


    $sqlRender = null;

    if (count($sqlGroupPrefix) > 0) {
      foreach ($sqlGroupPrefix as $prefix) {
        $prefixGroup = $prefix->prefix_group ? $prefix->prefix_group : 0;

        $sqlRender .= " when destination_type in ($prefix->listId) then '$prefixGroup'";
      }
    }



    $chargeLogs = " select sum(amount) as Amount, sum(count) as Duration,
                     case 
                     $sqlRender
                     else 'sub'
                     end as Direction
                     from charge_log
                     where charge_status=1
                         and insert_time >=?
                     and insert_time< ?
                     and charge_time>=?
                     and charge_time< ?
                     group by Direction";

    $resChargeLog = DB::select($chargeLogs, [$start_date, $end_date,$start_date, $end_date]);




    if(count($resChargeLog)>0)
    {
      foreach ($resChargeLog as $item)
      {
        $item->Direction= $groupObject->{$item->Direction};
      }
    }

//    $returnReport->sqlRender = $sqlRender;
    $returnReport->charge_logs = $resChargeLog;


    return $this->ApiReturn($returnReport, true, [], 200);
  }
}

