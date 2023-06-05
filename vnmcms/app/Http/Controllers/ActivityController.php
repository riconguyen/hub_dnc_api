<?php
namespace App\Http\Controllers;


use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ActivityController extends Controller
{
    //
    public function AddActivity($data, $table, $dataid, $rootid,  $action)
    {

      return [];
        $user = Auth::user()->id;

        $dataInsert = ['data_table' => $table, 'user_id' => $user,
            'data_id' => $dataid,
            'root_id'=>$rootid,
            'action' => $action,
            'raw_log' => json_encode($data)];

        DB::table("activity")
            ->insert($dataInsert);
    }

    public function GetActivity($table, $id)
    {
      return [];

        if ($id > 0) {
            $res = DB::table('activity AS a')
                ->join('users  AS b', 'a.user_id', '=', 'b.id')
                ->select('a.*', 'b.name')
                ->where('data_table', $table)
                ->where('data_id', $id)
                ->get();
        } else {
            $res = DB::table('activity')
                ->where('data_table', $table)
                ->get();
        }
        return response()->json($res);
    }
}
