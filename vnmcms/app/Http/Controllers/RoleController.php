<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    //
    public function getRoles(Request $request)
    {
        $user= $request->user;
        if($user->role != ROLE_ADMIN  &&  !$this->checkEntity($user->id, "EDIT_USERS"))
        {
            return ['error'=>'Permission denied'];
        }



        $res= DB::table('roles')
            ->whereNull('deleted_at')
            ->select('role_id','name','id')
            ->get();

        return response()->json($res, 200);
    }

    public function getUserByRole(Request $request)
    {
        $user= $request->user;
        if($user->role != ROLE_ADMIN &&  !$this->checkEntity($user->id, "EDIT_USERS") )
        {
            return ['error'=>'Permission denied'];
        }



        $validate = $request->validate([
            'id'=>'required|numeric|exists:roles,id',
        ]);

        $id=$request->id;

        $users= DB::table('roles as a')
            ->select('email','a.name','b.name as full_name')
            ->join('users as b', 'a.role','=','b.role_id')
            ->where('role_id', $id)
            ->get();
        return response()->json($users, 200);

    }
}
