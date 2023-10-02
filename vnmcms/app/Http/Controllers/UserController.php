<?php

namespace App\Http\Controllers;

use App\Entity;
use App\RoleEntity;
use App\Roles;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use  Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Psy\Output\PassthruPager;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Validator;

class UserController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



  public function index(Request $request)
    {



      $user= auth()->user();
      if(!$this->checkEntity($user->id, "LIST_USERS"))
      {

        Log::info($user->id."|".$user->email.'|' .$user->role.'  TRY TO GET UserController.index WITHOUT PERMISSION');
        return response()->json(['status'=>false, 'message'=>"Permission prohibit"],403);
      }


      $this->validate($request,[
        "count"=>'nullable|numeric|max:100',
        "page"=>'nullable|numeric|max:1000',
        "search"=>'nullable|alpha_num|max:50'
      ]);

      $count= request("count",10);
      $page= request("page",1);
      $search= request("q",null);

      $limit=($page-1)*$count;

      $sql="select u.id, u.name, u.email, u.role, u.updated_at, r.name as role_name  from users u 
        left join roles r on u.role= r.id
        left join customers c on c.account_id = u.id  
 WHERE 1=1  ";
      $sqlCount="select count(*) total from users u
    left join roles r on u.role= r.id
        left join customers c on c.account_id = u.id  
 WHERE 1=1  ";



      $param=[];

      if($search)
      {
        $sql .=" AND (u.email like ?  or u.name like ? )";
        $sqlCount .=" AND (u.email like ?  or u.name like ? )";
        array_push($param,"%$search%","%$search%");
      }
      $total=DB::select($sqlCount,$param)[0]->total;
      $sql .=" order by u.id desc LIMIT ?,? ";
      array_push($param,$limit,$count);

      $users= DB::select($sql, $param);




//      return response()->json(['lst'=>$users, 'status'=>true,'count'=>$total], 200);


return response()->json(['data'=>$users, 'count'=>$total]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

      $startTime=round(microtime(true) * 1000);
        $userb= $request->user;
        if($userb->role != ROLE_ADMIN )
        {
            return ['error'=>'Permission denied'];
        }

        $loginUser= Auth::user();

        if($loginUser->role!=1)
        {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$userb->email."|".$request->ip()."|".$request->url()."|"
            .json_encode($request->all())."|CREATE_USER|".$logDuration."|CREATE_USER_FAIL PERMISSION PROHIBIT");


            return response()->json(['status'=>false, 'message'=>'Out of permision'],403);
        }


        $credentials = Input::only('email', 'password','name', 'role');
        $validate = $request->validate([
            'email'=>'required|unique:users',
            'role'=>'required|numeric|exists:roles,id',
            'name'=>'required|max:50|',
            'password' =>'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
        ]);
        $credentials['password'] = Hash::make($credentials['password']);
        // $credentials['api_token'] = Hash::make($credentials['email']);
        try {
            $user = User::create($credentials);
        } catch (Exception $e) {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$userb->email."|".$request->ip()."|".$request->url()."|"
            .json_encode($request->except("password"))."|CREATE_USER|".$logDuration."|CREATE_USER_FAIL User already exists");

            return Response::json(['error' => 'User already exists.'], Illuminate\Http\Response::HTTP_CONFLICT);
        }
      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$userb->email."|".$request->ip()."|".$request->url()."|"
        .json_encode($request->except("password"))."|CREATE_USER|".$logDuration."|CREATE_USER_SUCCESS");

        $id = $user->id;
        // return response()->json(array('token'=>$id));
        return $id;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $startTime= round(microtime(true) * 1000);
        $userb= $request->user;
        if($userb->role != ROLE_ADMIN  &&  !$this->checkEntity($userb->id, "EDIT_USERS"))
        {
            return ['error'=>'Permission denied'];
        }

//      EDIT_USERS
//
//      if (!$this->checkEntity($user->id, "CONFIG_HOTLINE")) {
//        Log::info($user->email . '  TRY TO GET V1HotlineController.changeDirectionStatus WITHOUT PERMISSION');
//        return response()->json(['status' => false, 'message' => "Permission denied"], 403);
//      }
//



      if (!is_numeric($id)) {
            return response()->json(['response' => "error", 'message' => "user invalid"], 403);
        }

        $user = User::find($id);
        $r = $request->only('name', 'email', 'role', 'password');
        $loginUser = Auth::user();


        $validate = $request->validate([
            'name' => 'nullable|max:100',
            'email' => 'nullable|max:100',
            'role'=>'nullable|numeric|exists:roles,id'
        ]);
        if ($user->email != $request->email) {
            $request->validate([
                'email' => 'required|max:100|unique:users'
            ]);
            $user->email = $request->email;
        }
        if (!$request->input('password') == '') {
            $vaidate = $request->validate([
                'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ]);
            $user->password = bcrypt($request->input('password'));
        }
        if ($request->name) {
            $user->name = $request->name;
        }
        if ($loginUser->role == 1) {
            $user->role = $request->role;


        } else {
            if ($request->role && $user->role != $request->role && $request->role== ROLE_ADMIN) {
              $logDuration= round(microtime(true) * 1000)-$startTime;
              Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$userb->email."|".$request->ip()."|".$request->url()."|"
                .json_encode($request->all())."|UPDATE_USER_INFO|".$logDuration."|UPDATE_USER_INFO_FAIL PERMISSION PROHIBIT");
                return response()->json(['status' => false, 'message' => 'Out of permision', 'errors' => ['role' => 'You can not change role of administrator']], 403);
                // return response()->json(['response' => "error", 'message' => "You can not change role of administrator"], 422);
            }
        }
        $user->save();

      $logDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$userb->email."|".$request->ip()."|".$request->url()."|"
        .json_encode($request->except("password"))."|UPDATE_USER_INFO|".$logDuration."|UPDATE_USER_INFO_SUCCESS");




      return response()->json($user);



        //Flash::message('Your account has been updated!');
       // return Redirect::to('/account');


    }

    public  function changePassword(Request $request)
    {
      $startTime= round(microtime(true) * 1000);
        $userb= $request->user;
        $validate = $request->validate([
            'oldPassword'=>'required|max:50|',
            'newPassword' =>'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',

        ]);
        $current_password = Auth::User()->password;


       // return $current_password;
        if(Hash::check($request->input("oldPassword"), $current_password))
        {
            Log::info('Start change passs'.$userb->id);

            $obj_user = User::find($userb->id);
            $obj_user->password = bcrypt($request->input("newPassword"));
            $obj_user->api_token = null;
            $obj_user->remember_token = null;
            $obj_user->save();
            Log::info('Change pass complete change pass for '. $userb->id);
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$userb->email."|".$request->ip()."|".$request->url()."|"
            .json_encode($request->except("password"))."|CHANGE_PASSWORD|".$logDuration."|CHANGE_PASSWORD_SUCCESS");




          return response()->json(array('status' => true), 200);;
        }
        else
        {

          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$userb->email."|".$request->ip()."|".$request->url()."|"
            .json_encode($request->all())."|CHANGE_PASSWORD|".$logDuration."|CHANGE_PASSWORD_FAIL Invalid input data");


          $error = array('current-password' => 'Please enter correct current password');
            return response()->json(array('error' => $error), 403);
        }
    }



  public function captcha(Request $request)
  {
    $md5 = md5(rand());
    $text = substr($md5, 0, 8);
    $hashText = md5($text);
    $request->session()->put('sbc_captcha', $hashText);
    return view('captcha', ['key' => $text]);
  }

    public  function LoginWebSession(Request $request)
    {


      $startTime = round(microtime(true) * 1000);

        $credentials = $request->only('email', 'password');
        if (config('sbc.usingCaptcha')) {
            if (!$request->captcha || md5($request->captcha) != $request->session()->get('sbc_captcha')) {
                return response()->json([
                    'response' => 'error',
                    'message' => 'captcha_verify_failed',
                ], 200);
            }
        }

        $token = null;


      $validator = Validator::make($credentials, [
        'email' => 'required|unicode_valid|max:250',
        'password' => 'required|max:250',

      ]);
      if ($validator->fails()) {
        /** @var LOG $logDuration */
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $request->ip() . "|" . $request->url() .
          "|" . json_encode($request->all()) .
          "|LOGIN|" . $logDuration .
          "|LOGIN_FAIL Invalid data input ");
        /** @var LOG $logDuration */

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }




      try {
        $userLogin = User::where('email', $request->email)->first();
        if (!$userLogin)
        {
          return response()->json([
            'response' => 'error',
            'message' => 'invalid_email_or_password',
          ]);
        }

            if (!$token = JWTAuth::attempt($credentials)) {


              if($userLogin->fail_attempt_login > config("auth.login.attempt")-1 && ($userLogin->last_fail_login+$userLogin->retry_after) > time())
              {
                Log::info("LOGIN FAIL ATTEMPT");
                Log::info(json_encode($userLogin));
                return response()->json([
                  'response' => 'error',
                  'message' => 'To many attempt, please retry after: '.(($userLogin->last_fail_login+$userLogin->retry_after)-time().' second'),
                ],200);
              }
              else
              {

                $userLogin->fail_attempt_login ++;

                if ($userLogin->fail_attempt_login > config("auth.login.attempt"))
                {
                  $userLogin->retry_after = $userLogin->retry_after + config("auth.login.retry_after");
                }
                else
                {
                  $userLogin->retry_after = config("auth.login.retry_after");
                }

                $userLogin->last_fail_login = time();
              }

              $userLogin->save();


              $LogDuration= round(microtime(true) * 1000)-$startTime;
              Log::info(APP_NAME."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL_INVALID_EMAIL_OR_PASSWORD|".$LogDuration);

                return response()->json([
                    'response' => 'error',
                    'message' => 'invalid_email_or_password',
                ]);
            }
            else
            {
              $userLogin->fail_attempt_login =0;
              $userLogin->retry_after = 0;
              $userLogin->last_fail_login = time();
              $userLogin->save();

            }

        } catch (JWTAuthException $e) {
          $LogDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_NAME."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL_FAILED_TO_CREATE_TOKEN|".$LogDuration);

          return response()->json([
                'response' => 'error',
                'message' => 'failed_to_create_token',
            ]);
        }
        $user = Auth::user();

        if ($user->role != 0 ) {
            $user->api_token = $token;
            $user->save();
          $LogDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_NAME."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_SUCCESS|".$LogDuration);

            return response()->json([
                'response' => 'success',
                'result' => [
                    'token' => $token,
                ],
            ]);
        } else {
          $LogDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_NAME."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL_YOU_HAVE_NO_PERMISSION_TO_ACCESS|".$LogDuration);

          return response()->json([
                'response' => 'error',
                'message' => 'you_have_no_permission_to_access',
            ]);
        }


    }


  public function getListEntity() {



    $user= auth()->user();
    if(!$this->checkEntity($user->id, "GET_ROLE_ENTITY"))
    {

      Log::info($user->email.'  TRY TO GET UserController.getListEntity WITHOUT PERMISSION');
      return response()->json(['status'=>false, 'message'=>"Permission prohibit"],403);
    }

    $lstEntity = Entity::orderBy('entity_group')->get();


    return response()->json(['lst'=>$lstEntity],200);

  }



  public function getListRoles(Request $request)
  {
    $user = $request->user;
//    Log::info($user->id."GET LIST ROLES");


    $this->validate($request, ['q'=>'nullable|alpha_num|max:30']);
    $q= $request->q;

    $sql="select * from roles where deleted_at is null ";
    $param=[];

    if($q)
    {
      $sql .=" and name like ?";
      array_push($param, "%$q%");

    }



    $lstRole= DB::select($sql, $param);



    foreach ($lstRole as  $role)
    {


      $role->entity= DB::select("select re.id, e.entity_name, e.entity_key, e.entity_group from role_entity re join entity e on re.entity_id = e.id where re.role_id=? order by e.entity_group ", [$role->id]);

    }

    return response()->json(['lstRoles'=>$lstRole, 'status'=>true, 'count'=>count($lstRole)],200);
  }

  public function postRoles(Request $request) {

    $user= auth()->user();

    Log::info($user->id . "ADD OR EDIT  ROLES");

    if (!$this->checkEntity($user->id, "ADD_ROLE" )) {
      Log::info($user->email . '  TRY TO GET UserController.postRoles WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
    }

  $this->validate($request, [
    "name"=>'required|sql_char|max:50',
    "role_key"=>'required|sql_char|max:50',
    "description"=>'nullable|max:100'
  ]);



    $postData = $request->only("name", "description","role_key");

    $role_id = $request->id;

    if ($role_id) {

      $rolekeyExist = Roles::where('role_key', request('role_key'))->where('id','!=',$role_id)->first();
      if($rolekeyExist)
      {
        return $this->ApiReturn(['role_key'=>['Role key already exists']], false, "Role key already exist",422);
      }


      $role = Roles::where('id', $role_id)->first();



    } else {

      $rolekeyExist = Roles::where('role_key', request('role_key'))->first();
      if($rolekeyExist)
      {
        return $this->ApiReturn(['role_key'=>['Role key already exists']], false, "Role key already exist",422);
      }


      $role = new Roles();


    }
    $role->role_key = $postData['role_key'];
    $role->timestamps=false;
    $role->name = $postData['name'];
    $role->description =request('description',null);

    $role->save();

    $role->edit= $role_id?true:false;

    return response()->json(['roles' => $role, 'status' => true], 200);
  }

  public function setRemoveRoles(Request $request)
  {
    $startTime = round(microtime(true) * 1000);
    $user= auth()->user();

    Log::info($user->id . "ADD OR EDIT  ROLES");


    if (!$this->checkEntity($user->id, "REMOVE_ROLE")) {
      Log::info($user->email . '  TRY TO GET UserController.setRemoveRoles WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
    }


    $role_key = $request->role_key;
    if ($role_key=='ADMIN'|| $role_key=="GUEST") {
      Log::info($user->email . '  TRY TO GET UserController.setRemoveRoles WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
    }

    $role= Roles::where('role_key', $role_key)->first();
    if($role)
    {

      User::where('role', $role->id)->update(['role'=>0]);

      $role->deleted_at= date("Y-m-d H:i:s");
      $role->role_key= $role->role_key.".".time();

      $role->timestamps= false;

      $role->save();

    }


    $logDuration = round(microtime(true) * 1000) - $startTime;
    Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ROLE_MANANGER_REMOVE_ROLE|" . $logDuration . "|ROLE_MANANGER_REMOVE_ROLE_SUCCESS");


    return response()->json(['status' => true], 200);



  }

  public function setEntityRole(Request $request) {
    $entityId = $request->entity_id;
    $roleId = $request->role_id;
    $user= $request->user();
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ADD_ROLE_ENTITY")) {
      Log::info($user->email . '  TRY TO GET UserController.setEntityRole WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
    }

    DB::beginTransaction();
    try {
      if (Roles::where("id", $roleId)->exists()) {
        $entity= Entity::where("id", $entityId)->first();
        if ($entity) {
          $roleEntity = RoleEntity::where("entity_id", $entityId)->where("role_id", $roleId)->first();
          if ($roleEntity) {
            $logDuration = round(microtime(true) * 1000) - $startTime;
            Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|SET_ENTITY_ROLE|" . $logDuration . "|Duplicate value, entity already exists");

            return response()->json(['status' => false, 'roleEntity' => $roleEntity, 'message' => "Dublicate role entity on system"], 422);
          } else {
            $roleEntity = new RoleEntity();
            $roleEntity->role_id = $roleId;
            $roleEntity->entity_id = $entityId;
            $roleEntity->save();
            DB::commit();
          }
        } else {
          DB::rollBack();
          $logDuration = round(microtime(true) * 1000) - $startTime;
          Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|SET_ENTITY_ROLE|" . $logDuration . "|Not found entity on system");

          return response()->json(['status' => false, 'entity' => $entityId, 'message' => "Not found entity on system"], 403);
        }
      } else {
        DB::rollBack();
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|SET_ENTITY_ROLE|" . $logDuration . "|Not found role_id on system");

        return response()->json(['status' => false, 'role' => $roleId, 'message' => "Not found role_id on system"], 403);
      }
    } catch (\Exception $exception) {
      DB::rollBack();
      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|SET_ENTITY_ROLE|" . $logDuration . "|Internal error fail");

      return response()->json(['status' => false,   'message' => "Internal error"], 500);
    }

    $roleEntity->entity_name= $entity->entity_name;
    $logDuration = round(microtime(true) * 1000) - $startTime;
    Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|SET_ENTITY_ROLE|" . $logDuration . "|setEntityRole|SUCCESS");


    return response()->json(['status' => true, 'roleEntity' => $roleEntity], 200);
  }


  public function removeEntityRole (Request $request)
  {

    $roleEntityId= $request->id;
    $user= $request->user();
    Log::info($user);
    $startTime = round(microtime(true) * 1000);
    if (!$this->checkEntity($user->id, "ADD_ROLE_ENTITY" )) {
      Log::info($user->email . '  TRY TO GET UserController.removeEntityRole WITHOUT PERMISSION');
      return response()->json(['status' => false, 'message' => "Permission prohibit"], 403);
    }


    $roleEntity = RoleEntity::where("id", $roleEntityId)->first();
    if($roleEntity)
    {

      if($roleEntity->role_id==1 || $roleEntity->role_id==0)
      {
        return response()->json(['status' => false, 'role_entity_id'=>$roleEntityId,'message'=>"You cannot remove default Admin entity "], 402);
      }
      else
      {
        $roleEntity->delete();
        $logDuration = round(microtime(true) * 1000) - $startTime;
        Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ROLE_MANANGER_REMOVE_ROLE|" . $logDuration . "|ROLE_MANANGER_REMOVE_ROLE_SUCCESS");



        return response()->json(['status' => true, 'role_entity_id'=>$roleEntityId,'message'=>"Remove success"], 200);
      }

    }
    else
    {

      $logDuration = round(microtime(true) * 1000) - $startTime;
      Log::info(APP_API . "|" . date("Y-m-d H:i:s", time()) . "|" . $user->email . "|" . $request->ip() . "|" . $request->url() . "|" . json_encode($request->all()) . "|ROLE_MANANGER_REMOVE_ROLE|" . $logDuration . "|ROLE_MANANGER_REMOVE_ROLE_Fails");

      return response()->json(['status' => false, 'message' => "Not found role entity alias "], 422);

    }



  }
  public function getAms(Request $request)
  {


    $sql="select u.name as username, u.id, u.email, roles.name role_name from users u join 
roles on roles.id= u.role
join
role_entity re on roles.id=re.role_id
where re.entity_id=(select id from entity where entity.entity_key='AM')";


    $resUsers = DB::select($sql);


    return response()->json(['status' => true, 'data'=>$resUsers], 200);


  }


}
