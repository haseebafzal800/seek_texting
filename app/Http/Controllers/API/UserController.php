<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserOtp;
use App\Models\DriverDocument;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\DriverResource;
use Illuminate\Support\Facades\Password;
use App\Models\AppSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\DriverRequest;
use App\Models\RiderDetail;
use App\Models\Music;
use App\Models\DriverPreference;
use Twilio;
use Twilio\Rest\Client;
use App\Http\Requests\ForgetPasswordRequest;
use Illuminate\Support\Facades\Validator;
use DB;

class UserController extends Controller
{
    public function isonline()
    {
        User::where('id', auth()->user()->id)->update(['is_online' => 1]);
        return response()->json([
            'status' => true,
            'message' => 'User is Online and Available',
        ]);
    }
    public function forgetPasswordOtp(ForgetPasswordRequest $request)
    {
        $userOtp   = UserOtp::where('otp', $request->otp)->first();
        $now = now();
        if (!$userOtp) {
            return response()->json([
                'status' => false,
                'message' => 'Your OTP is not correct',
            ]);
        } else if ($userOtp && $now->isAfter($userOtp->expire_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Your OTP has been expired',
            ]);
        }
        $user = User::whereId($userOtp->user_id)->first();
        if ($user) {
            $userOtp->update([
                'expire_at' => now()
            ]);
            $user->update([
                'password' => Hash::make($request->password)
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Password reset Successfully',
                'data' => User::all()
            ]);
        }

        // return redirect()->route('otp.login')->with('error', 'Your Otp is not correct');
    }

    public function getforgetPasswordOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|exists:users,email'
        ]);
        $user = User::where('email', $request->email)->first();
        /* User Does not Have Any Existing OTP */
        $userOtp = UserOtp::where('user_id', $user->id)->first();
        $now = now();
        // if($userOtp && $now->isBefore($userOtp->expire_at)){
        //     return $userOtp;
        // }
        /* Create a New OTP */
        if ($userOtp) {
            UserOtp::where('user_id', $user->id)->update([
                'user_id' => $user->id,
                'otp' => rand(123456, 999999),
                'expire_at' => $now->addMinutes(10)
            ]);
            $userOtp = UserOtp::where('user_id', $user->id)->first();
            $userOtp->sendSMS($user->contact_number);
            return response()->json([
                'status' => true,
                'message' => 'Your 6 digit Otp has been resent.',
            ]);
        } else {
            $userOtp = UserOtp::create([
                'user_id' => $user->id,
                'otp' => rand(123456, 999999),
                'expire_at' => $now->addMinutes(10)
            ]);
            $userOtp->sendSMS($user->contact_number);
            return response()->json([
                'status' => true,
                'message' => 'Your 6 digit Otp has been sent.',
            ]);
        }
    }

    public function driverPreferences()
    {
        return response()->json([
            'data' => DriverPreference::all(),
            'status' => true,
            'message' => 'Driver Preferences retrieved Successfully',
        ]);
    }

    public function musics()
    {
        return response()->json([
            'data' => \DB::table('musics')->get(),
            'status' => true,
            'message' => 'musics retrieved Successfully',
        ]);
    }

    public function emailCheck(Request $request)
    {
        $input['email'] = $request->email;
        $input['username'] = $request->username;
        // Must not already exist in the `email` column of `users` table
        $rules = array('email' => 'unique:users,email', 'username' => 'unique:users,username');

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->getMessageBag()->get('*')
            ]);
        } else {
            return response()->json([
                'status' => true,
                'message' => 'Valid Email and Username.',
            ]);
        }
    }
    public function generateOtp(Request $request)
    {
        // $request->validate([
        //     'phone' => 'required|exists:users,contact_number'
        // ]);
        $user = User::where('contact_number', $request->phone)->first();
        // if(!isset($user))
        // {
        //     return response()->json([
        //         'status'=>false, 
        //         'message'=>'User with this contact number not found.', 
        //   ]);
        // }
        $now = now();
        // if(isset($user))
        // {
        //      /* User Does not Have Any Existing OTP */
        //     $userOtp = UserOtp::where('user_id', $user->id)->first();

        //     // if($userOtp && $now->isBefore($userOtp->expire_at)){
        //     //     return $userOtp;
        //     // }
        //     /* Create a New OTP */
        //     if($userOtp)
        //     {
        //         UserOtp::where('user_id', $user->id)->update([
        //             'user_id' => $user->id,
        //             'otp' => mt_rand(100000,999999),
        //             'expire_at' => $now->addMinutes(10)
        //         ]);
        //         $userOtp=UserOtp::where('user_id', $user->id)->first();
        //         $userOtp->sendSMS($user->contact_number);
        //         return response()->json([
        //                 'status'=>true, 
        //                 'message'=>'Your 6 digit Otp has been resent.', 
        //         ]);
        //     }
        //     else
        //     {
        //         $userOtp=UserOtp::create([
        //         'user_id' => $user->id,
        //         'otp' => mt_rand(100000,999999),
        //         'expire_at' => $now->addMinutes(10)
        //        ]);
        //         $userOtp->sendSMS($user->contact_number);
        //             return response()->json([
        //                     'status'=>true, 
        //                     'message'=>'Your 6 digit Otp has been sent.', 
        //                 ]);
        //     }
        // }
        // else
        // {
        $userOtp = UserOtp::create([
            'user_id' => 0,
            'otp' => mt_rand(100000, 999999),
            'expire_at' => $now->addMinutes(10)
        ]);

        $userOtp->sendSMS($request->phone);
        return response()->json([
            'status' => true,
            'message' => 'Your 6 digit Otp has been sent.',
        ]);
        // }
    }
    public function loginWithOtp(Request $request)
    {
        /* Validation */
        // $request->validate([
        //     'user_id' => 'required|exists:users,id',
        //     'otp' => 'required'
        // ]);  
        /* Validation Logic */
        // $userOtp   = UserOtp::where('otp', $request->otp)->first();
        // $now = now();
        // if (!$userOtp) {
        //     return response()->json([
        //         'status'=>true, 
        //         'message'=>'Your OTP is not correct', 
        //     ]);
        // }else if($userOtp && $now->isAfter($userOtp->expire_at)){
        //     return response()->json([
        //         'status'=>true, 
        //         'message'=>'Your OTP has been expired', 
        //     ]);
        // }

        $userOtp = UserOtp::whereOtp($request->otp)->first();
        if ($userOtp) {
            $userOtp->update([
                'expire_at' => now()
            ]);
            // Auth::login($user);
            return response()->json([
                'status' => true,
                'message' => 'OTP Matched',
                // 'data'=>User::all()
            ]);
        } else {
            return response()->json([
                'status' => true,
                'message' => 'Your OTP is not correct',
            ]);
        }

        // return redirect()->route('otp.login')->with('error', 'Your Otp is not correct');
    }
    public function twillio2Old(Request $request)
    {
        $twilio_number = "+14694253422";
        $account_sid = getenv("TWILIO_SID");
        // $auth_token = getenv("TWILIO_AUTH_TOKEN");
        // $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $auth_token = "025cd6ec820c2efb5012b99a2e5f050a";
        $account_sid = "AC0e9de838f1a9c0fdea308d4f2a16f92a";
        $twilio_verify_sid = "VAac213514ead4766e3a1df095ac451a8b";
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL,"https://verify.twilio.com/v2/Services/VA286b596a315e55a21afa6416e8efd9bd/Verifications \
        // --data-urlencode 'To=+14694253422' \
        // --data-urlencode 'Channel=sms' \
        // -u $account_sid:$auth_token");
        // $server_output = curl_exec($ch);
        // $error    = curl_errno($ch);
        // dd( $error);
        // curl_close ($ch);

        // $twilio_verify_sid = "MG9a4512cff0eae08b822bd34f5d7843c9";
        $client = new Client($account_sid, $auth_token);
        // dd($client); 
        $client->verify->v2->services($twilio_verify_sid)
            ->verifications
            ->create($twilio_number, "sms");


        // $client->verify->v2->services($twilio_verify_sid)
        //     ->verifications
        //     ->create($twilio_number, "sms");

        // $client->messages->create(
        //     // Where to send a text message (your cell phone?)
        //     '+92 316 5328440',
        //     array(
        //         'from' => $twilio_number,
        //         'body' => 'I sent this message in under 10 minutes!'
        //     )
        // );
        dd("fkfk");
    }
    public function verifyotp(Request $request)
    {
        // $data = $request->validate([
        //     'verification_code' => ['required', 'numeric'],
        //     'phone_number' => ['required', 'string'],
        // ]);
        // dd("dd");
        /* Get credentials from .env */
        // $token = getenv("TWILIO_AUTH_TOKEN");
        // $twilio_sid = getenv("TWILIO_SID");
        // $twilio_verify_sid = getenv("TWILIO_VERIFY_SID");
        $token = "025cd6ec820c2efb5012b99a2e5f050a";
        $twilio_sid = "AC0e9de838f1a9c0fdea308d4f2a16f92a";
        $twilio_verify_sid = "VA61fac4e4f8b7c95c2ff54bb689102332";
        $twilio = new Client($twilio_sid, $token);
        $verification_check = $twilio->verify->v2->services("VA61fac4e4f8b7c95c2ff54bb689102332")
            ->verificationChecks
            ->create(
                [
                    "to" => "+14694253422",
                    "code" => "3026449"
                ]
            );
        return  $verification_check;
        //      print($verification_check->status);
        // $verification = $twilio->verify->v2->services($twilio_verify_sid)
        //     ->verificationChecks
        //     ->create(['code' => $data['verification_code'], 'to' => "+14694253422"]);
        //     dd($verification);
        // if ($verification->valid) {
        //     $user = tap(User::where('contact_number', $data['phone_number']))->update(['is_verified_driver' => 1]);
        //     /* Authenticate user */
        //     Auth::login($user->first());
        //     return redirect()->route('home')->with(['message' => 'Phone number verified']);
        // }
        return back()->with(['phone_number' => $data['phone_number'], 'error' => 'Invalid verification code entered!']);
    }
    public function register(UserRequest $request)
    {
        $valid = validate_mobile($request->contact_number);
        $call_forwarding_number = validate_mobile($request->call_forwarding_number);
        if (!$valid || !$call_forwarding_number) {
            $response = [
                'data' => '',
                'status' => false,
                'message' => (!$valid?'Twilio Number not valid':'Call forwarding number is not valid'),
            ];
        } else {
            $exist = User::where(['contact_number' => $valid])->count();
            if ($exist == 0) {
                $input = $request->all();
                $password = $input['password'];
                $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'user';
                $input['password'] = Hash::make($password);

                $input['display_name'] = $input['first_name'] . " " . $input['last_name'];
                $input['contact_number'] = $valid;
                $input['call_forwarding_number'] = $call_forwarding_number;

                $user = User::create($input);

                $user->assignRole($input['user_type']);

                $message = __('message.save_form', ['form' => __($input['user_type'])]);
                $user->api_token = $user->createToken('auth_token')->plainTextToken;
                // $user->profile_image = getSingleMedia($user, 'profile_image', null);

                $response = [
                    'status' => true,
                    'message' => $message,
                    'data' => User::where('id', $user->id)->first()
                ];
            } else {
                $response = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Number already been taken.',
                ];
            }
        }

        return json_custom_response($response);
    }


    public function login(Request $request)
    {

        if (Auth::attempt(['username' => request('username'), 'email' => function ($query) {
            $query->orwhere('email', request('username'));
        }, 'password' => request('password'), 'is_deleted' => '0', 'status' => 'active'])) {
            $user = Auth::user();

            $success = User::where('id', $user->id)->first();

            $success['api_token'] = $user->createToken('auth_token')->plainTextToken;

            return json_custom_response(['data' => $success, 'status' => true, 'message' => "Login Successfully"], 200);
        } else {

            $response = [
                'status' => false,
                'message' => "These credentials do not match our records."
            ];
            return response()->json($response, 400);
        }
    }

    public function userList(Request $request)
    {
        $user_type = isset($request['user_type']) ? $request['user_type'] : 'rider';

        $user_list = User::query();

        $user_list->when(request('user_type'), function ($q) use ($user_type) {
            return $q->where('user_type', $user_type);
        });

        $user_list->when(request('fleet_id'), function ($q) {
            return $q->where('fleet_id', request('fleet_id'));
        });

        if ($request->has('is_online') && isset($request->is_online)) {
            $user_list = $user_list->where('is_online', request('is_online'));
        }

        if ($request->has('status') && isset($request->status)) {
            $user_list = $user_list->where('status', request('status'));
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page == -1) {
                $per_page = $user_list->count();
            }
        }

        $user_list = $user_list->paginate($per_page);

        if ($user_type == 'driver') {
            $items = DriverResource::collection($user_list);
        } else {
            $items = UserResource::collection($user_list);
        }

        $response = [
            'status' => true,
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];

        return json_custom_response($response);
    }

    public function userDetail(Request $request)
    {
        $id = $request->id;
        $user = User::where('id', $id)->first();
        if (empty($user)) {
            $message = __('message.user_not_found');
            return json_message_response($message, 400);
        }
        if ($user->user_type == 'driver') {
            $user_detail = new DriverResource($user);
        } else {
            $user_detail = new UserResource($user);
        }

        $response = [
            'status' => true,
            'data' => $user_detail
        ];
        return json_custom_response($response);
    }
    public function getProfile()
    {
        $d = User::where(['id' => Auth::user()->id])->get();
        $resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($resp);
    }
    public function changePassword(Request $request)
    {
        $user = User::where('id', Auth::user()->id)->first();
        if ($user == "") {
            $message = __('message.user_not_found');
            return json_message_response($message, 400);
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->new_password, $hashedPassword);
        if ($match) {
            if ($same_exits) {
                $message = __('message.old_new_pass_same');
                return json_message_response($message, 400);
            }

            $user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            $message = __('message.password_change');
            return json_message_response($message, 200);
        } else {
            $message = __('message.valid_password');
            return json_message_response($message, 400);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if ($request->has('id') && !empty($request->id)) {
            $user = User::where('id', $request->id)->first();
        }
        if ($user == null) {
            return json_message_response(__('message.no_record_found'), 400);
        }
        if ($request->filled('new_password')) {
            $request->request->add(['password' => Hash::make($request->new_password)]);
            $request->request->remove('new_password');
        }

        $user->fill($request->all())->update();

        if (isset($request->profile_image) && $request->profile_image != null) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        $user_data = User::find($user->id);

        /*if($user_data->userDetail != null && $request->has('user_detail') ) {
            $user_data->userDetail->fill($request->user_detail)->update();
        } else if( $request->has('user_detail') && $request->user_detail != null ) {
            $user_data->userDetail()->create($request->user_detail);
        }*/

        /*if($user_data->userBankAccount != null && $request->has('user_bank_account')) {
            $user_data->userBankAccount->fill($request->user_bank_account)->update();
        } else if( $request->has('user_bank_account') && $request->user_bank_account != null ) {
            $user_data->userBankAccount()->create($request->user_bank_account);
        }*/

        $message = __('message.updated');
        // $user_data['profile_image'] = getSingleMedia($user_data,'profile_image',null);
        unset($user_data['media']);

        /*if( $user_data->user_type == 'driver') {
            $user_resource = new DriverResource($user_data);
        } else {
            $user_resource = new UserResource($user_data);
        }*/

        $response = [
            'status' => true,
            'data' => $request->all(),
            // 'data' => $user_resource,
            'message' => $message
        ];

        return json_custom_response($response);
    }

    public function logout(Request $request)
    {
        if ($request->is('api*')) {
            $request->user()->currentAccessToken()->delete();
            $response = [
                'status' => true,
                'data' => null,
                'message' => "Successfully Logging out"
            ];
            return json_custom_response($response);
        }
    }


    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $response = Password::sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? response()->json(['message' => __($response), 'status' => true], 200)
            : response()->json(['message' => __($response), 'status' => false], 400);
    }

    public function socialLogin(Request $request)
    {
        $input = $request->all();

        if ($input['login_type'] === 'mobile') {
            $user_data = User::where('username', $input['username'])->where('login_type', 'mobile')->first();
        } else {
            $user_data = User::where('email', $input['email'])->first();
        }

        if (!in_array($user_data->user_type, ['admin', request('user_type')])) {
            $message = __('auth.failed');
            return json_message_response($message, 400);
        }

        if ($user_data != null) {
            if (!isset($user_data->login_type) || $user_data->login_type  == '') {
                if ($request->login_type === 'google') {
                    $message = __('validation.unique', ['attribute' => 'email']);
                } else {
                    $message = __('validation.unique', ['attribute' => 'username']);
                }
                return json_message_response($message, 400);
            }
            $message = __('message.login_success');
        } else {

            if ($request->login_type === 'google') {
                $key = 'email';
                $value = $request->email;
            } else {
                $key = 'username';
                $value = $request->username;
            }

            if ($request->login_type === 'mobile' && $user_data == null) {
                $otp_response = [
                    'status' => true,
                    'is_user_exist' => false
                ];
                return json_custom_response($otp_response);
            }

            $password = !empty($input['accessToken']) ? $input['accessToken'] : $input['email'];

            $input['display_name'] = $input['first_name'] . " " . $input['last_name'];
            $input['password'] = Hash::make($password);
            $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'rider';
            $user = User::create($input);
            if ($user->userWallet == null) {
                $user->userWallet()->create(['total_amount' => 0]);
            }
            $user->assignRole($input['user_type']);

            $user_data = User::where('id', $user->id)->first();
            $message = __('message.save_form', ['form' => $input['user_type']]);
        }

        $user_data['api_token'] = $user_data->createToken('auth_token')->plainTextToken;
        $user_data['profile_image'] = getSingleMedia($user_data, 'profile_image', null);

        $is_verified_driver = false;
        if ($user_data->user_type == 'driver') {
            $is_verified_driver = $user_data->is_verified_driver; // DriverDocument::verifyDriverDocument($user_data->id);
        }
        $user_data['is_verified_driver'] = (int) $is_verified_driver;
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $user_data
        ];
        return json_custom_response($response);
    }

    public function updateUserStatus(Request $request)
    {
        $user_id = $request->id ?? auth()->user()->id;

        $user = User::where('id', $user_id)->first();

        if ($user == "") {
            $message = __('message.user_not_found');
            return json_message_response($message, 400);
        }
        if ($request->has('status')) {
            $user->status = $request->status;
        }
        if ($request->has('is_online')) {
            $user->is_online = $request->is_online;
        }
        if ($request->has('is_available')) {
            $user->is_available = $request->is_available;
        }
        if ($request->has('latitude')) {
            $user->latitude = $request->latitude;
        }
        if ($request->has('longitude')) {
            $user->longitude = $request->longitude;
        }
        $user->save();

        /*if( $user->user_type == 'driver') {
            $user_resource = new DriverResource($user);
        } else {
            $user_resource = new UserResource($user);
        }*/
        $message = __('message.update_form', ['form' => __('message.status')]);
        $response = [
            'status' => true,
            // 'data' => $user_resource,
            'data' => $request->all(),
            'message' => $message
        ];
        return json_custom_response($response);
    }

    public function updateAppSetting(Request $request)
    {
        $data = $request->all();
        AppSetting::updateOrCreate(['id' => $request->id], $data);
        $message = __('message.save_form', ['form' => __('message.app_setting')]);
        $response = [
            'data' => AppSetting::first(),
            'message' => $message
        ];
        return json_custom_response($response);
    }

    public function getAppSetting(Request $request)
    {
        if ($request->has('id') && isset($request->id)) {
            $data = AppSetting::where('id', $request->id)->first();
        } else {
            $data = AppSetting::first();
        }

        return json_custom_response($data);
    }

    public function deleteUserAccount(Request $request)
    {
        $id = auth()->id();
        $user = User::where('id', $id)->first();
        $message = __('message.not_found_entry', ['name' => __('message.account')]);

        if ($user != '') {
            $user->delete();
            $message = __('message.account_deleted');
        }

        return json_custom_response(['message' => $message, 'status' => true]);
    }
}
