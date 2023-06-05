<?php
namespace App\Http\Controllers;

use App\Customers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use JWTAuthException;
use App\User;
use Illuminate\Support\Facades\DB;
use Validator;

class ApiController extends Controller
{
    //
    public function __construct()
    {
        $this->user = new User;
    }



    protected function postGoogleCaptcha($gcaptchavalue, $remoteIp) {
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $field = 'secret=6LcrUUwUAAAAABUgHvvgnoubAQb0YvJS1UNgXiWO&response=' . $gcaptchavalue ;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        $response = json_decode($response);
        return $response;
    }


    private function setUpRenderDateRange()
    {


        $lastDate= DB::table('report_days')
            ->orderBy('full_time','desc')
            ->select('full_time')
            ->first();

        $startDate= new \DateTime($lastDate->full_time);
        $endDate=new \DateTime(date('Y-m-d'));


        for($i = $startDate->modify('+1 day'); $i <= $endDate; $i->modify('+1 day')){
         //   $dateRange[]= array(date_format($i,"Y-m-d"), );
            $dateRange[]= ['full_time'=>date_format($i,"Y-m-d"),   'report_year'=>date_format($i,"Y"),
                'report_month'=>date_format($i,"m"),
                'month_name'=>date_format($i,"F"),
                'report_day'=> date_format($i,"d")];

        }

        if(isset($dateRange)&& count($dateRange)>0)
        {
          if(DB::table("report_days")->where('full_time',date_format($i,"Y-m-d"))->exists())
          {

          }
          else
          {
            DB::table('report_days')
              ->insert($dateRange);
          }

        }

        return ('success');
    }


    public function login(Request $request)
    {
      $startTime= round(microtime(true) * 1000);
        $this->setUpRenderDateRange(); // Build data for report

        $credentials = $request->only('email', 'password');


      $validator = Validator::make($credentials, ['email'=>'required|max:250|exists:users,email','password'=>'required|min:2|max:250']);
      // Trả về lỗi nếu sai
      if ($validator->fails()) {

        $logDuration= round(microtime(true) * 1000)-$startTime;
//        Log::info(APP_API."|".date("Y-m-d H:i:s",time())."|".$user->email."|".$request->ip()."|".$request->url()."|".json_encode($validData)."|GET_CUSTOMERS|".$logDuration."|Invalid parameter");

        return $this->ApiReturn($validator->errors(), false, 'The given data was invalid', 422);
      }



        $token = null;
        try {
          $userLogin = User::where('email', $request->email)->first();

            if (!$token = JWTAuth::attempt($credentials)) {
              if($userLogin->fail_attempt_login > config("auth.login.attempt")-1 && ($userLogin->last_fail_login+$userLogin->retry_after) > time())
              {
                Log::info("LOGIN FAIL ATTEMPT");
                Log::info(json_encode($userLogin));
                return response()->json([
                  'response' => 'error',
                  'message' => 'To many attempt, please retry after: '.(($userLogin->last_fail_login+$userLogin->retry_after)-time().' second'),
                ],403);
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



              $logDuration= round(microtime(true) * 1000)-$startTime;
              Log::info(APP_API."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL|".$logDuration);
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
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL|".$logDuration);
            return response()->json([
                'response' => 'error',
                'message' => 'failed_to_create_token',
            ]);
        }
        $user = Auth::user();
        $user->api_token = $token;
        $user->save();
        if ($user->role == 1) {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_SUCCESS|".$logDuration);
            return response()->json([
                'response' => 'success',
                'result' => [
                    'token' => $token,
                ],
            ]);
        } else {
          $logDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL|".$logDuration);
            return response()->json([
                'response' => 'error',
                'message' => 'you_have_no_permission_to_access',
            ]);
        }
    }



    public function loginWeb(Request $request)
    {
      $startTime= round(microtime(true) * 1000);


        $this->setUpRenderDateRange(); // Build data for report

        $credentials = $request->only('email', 'password');

      if ($this->hasTooManyLoginAttempts($request)) {
        $this->fireLockoutEvent($request);
        return response()->json(['error' => 'Too many logins'], 400);
      }

        $token = null;
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
              $this->incrementLoginAttempts($request);

              $LogDuration= round(microtime(true) * 1000)-$startTime;
              Log::info(APP_API."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL_INVALID_PASSWORD|".$LogDuration);
              return [];
                return response()->json([
                    'response' => 'error',
                    'message' => 'invalid_email_or_password',
                ],403);
            }
        } catch (JWTAuthException $e) {
          $LogDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL_ERROR_CREATE_TOKEN|".$LogDuration);
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
            Log::info(APP_API."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_SUCCESS|".$LogDuration);

            return response()->json([
                'response' => 'success',
                'result' => [
                    'token' => $token,
                ],
            ]);
        } else {

          $LogDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".$request->email."|".$request->ip()."|".$request->url()."|LOGIN_FAIL_NO_PERMISSION|".$LogDuration);

            return response()->json([
                'response' => 'error',
                'message' => 'you_have_no_permission_to_access',
            ]);
        }
    }

    public function getAuthUser(Request $request)
    {
        $login = Auth::user();
        $user = JWTAuth::toUser($request->token);
        if (Auth::check() && $login->api_token == $request->token && $user->role !=0) {
            // Get customer
            $enterprise=DB::table('customers')
                ->where('account_id', $user->id)
                ->first();

            return response()->json(['user' => $user,'enterprise'=>$enterprise, 'response' => 'success'], 200);
        } else {
            return response()->json([
                'response' => 'error',
                'message' => 'you_are_not_login_system',
            ], 403);
        }
    }

    public function check(Request $request)
    {
      $startTime= round(microtime(true) * 1000);
        $this->setUpRenderDateRange(); // Build data for report
        $login = Auth::user();

        $user = JWTAuth::toUser($request->token);
        if (Auth::check() && $login->api_token == $request->token && $login->role !=0) {


          $entityList= $this->getEntity($user->id);

          $AccountToUser= Customers::where('account_id', $user->id)->select('id','enterprise_number')->first();



            return response()->json(['role_id' => $user->role, 'acc'=>$AccountToUser,  'name' => $user->name,'entity'=>$entityList, 'user_id'=>$user->id]);


        } else {

          $LogDuration= round(microtime(true) * 1000)-$startTime;
          Log::info(APP_API."|".$request->token."|".$request->ip()."|".$request->url()."|INVALID_TOKEN|".$LogDuration);



          JWTAuth::invalidate($request->token);
            return response()->json([
                'response' => 'error',
                'message' => 'you_are_not_login_system',
            ], 403);
        }
        return response()->json(['id' => $user->id, 'name' => $user->name]);
    }

    public function logOutApi(Request $request)
    {
      $startTime= round(microtime(true) * 1000);


      $user = Auth::user();
        $user->api_token = null;
        $user->save();
        Auth::logout();
        JWTAuth::invalidate($request->token);

      $LogDuration= round(microtime(true) * 1000)-$startTime;
      Log::info(APP_API."|".$user->email."|".$request->ip()."|".$request->url()."|LOGOUT_SUCCESS|".$LogDuration);

        return response()->json([
            'response' => 'error',
            'message' => 'you_are_logged_out',
        ], 403);
    }
}
