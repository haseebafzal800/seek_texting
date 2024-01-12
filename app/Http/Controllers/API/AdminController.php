<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Categories;
use App\Models\Contactlist;
use App\Models\User;
use App\Models\WithdrawRequest;
use App\Models\Banned_words;
use Illuminate\Support\Facades\Hash;
use App\Models\Lists;
use App\Models\Campaigns;
use App\Models\AppSetting;
use Carbon\CarbonPeriod;
use App\Models\Campaign_detail;
use App\Models\SmsLimitCount;

class AdminController extends Controller
{
    public $resp;
    public function __construct()
    {
        $this->resp = ['data' => '', 'status' => false, 'message' => 'Un-authorized access'];
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->user_type == 'admin')
                return $next($request);
            else
                return response()->json($this->resp);
        }, ['except' => 'getAppSetting']);
    }

    public function create_category(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|unique:categories,name,NULL,NULL,deleted_at,NULL'

        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ];
        } else {
            $resp = array();
            $data = new Categories;
            $data->name = $request->name;
            $data->status = $request->status ?? 'active';
            // $data->user_id = auth()->user()->id;
            $ok = $data->save();
            if ($ok) {
                $resp = [
                    'data' => $data->id,
                    'status' => true,
                    'message' => 'Category added successfully',
                ];
            } else {
                $resp = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Category added fail',
                ];
            }
        }
        return response()->json($resp);
    }
    public function getCategories($user_id = null)
    {

        $d = Categories::get();
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function destroyCategory($id)
    {
        if (Categories::find($id)->delete()) {
            $del_list['is_deleted'] = '1';
            $lists = Lists::where('category_id', $id)->get();
            foreach ($lists as $list) {
                Contactlist::where('list_id', $list->id)->update(['deleted_at' => now()]);
            }
            Lists::where('category_id', $id)->update(['deleted_at' => now()]);
            return response()->json([
                'data' => '',
                'status' => true,
                'message' => 'Category delete successfully',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Category delete fail',
            ]);
        }
    }
    public function getCategoryById($id = null)
    {
        $d = Categories::where(['id' => $id])->get();
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function updateCategory(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            // 'name' => 'required|unique:categories,name,'.$request->id

        ]);
        if ($validator->fails()) {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);
        } else {
            $ok = Categories::where(['id' => $request->id])->first();
            if ($ok) {
                $done = $ok->update($request->all());
                if ($done) {
                    return response()->json([
                        'data' => '',
                        'status' => true,
                        'message' => 'Category updated successfully',
                    ]);
                } else {
                    return response()->json([
                        'data' => '',
                        'status' => false,
                        'message' => 'Category update fail',
                    ]);
                }
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data not found',
                ]);
            }
        }
    }
    /***Contacts section***/
    public function create_contact(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'contact' => 'required'
            // 'contact' => 'required|digits_between:10,15|numeric'

        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ];
        } else {
            $valid = validate_mobile($request->contact);
            if (!$valid) {
                $resp = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Number not valid',
                ];
            } else {
                $resp = array();

                $data = new Contactlist;
                $data->name = $request->name ?? '';
                $data->contact = $valid;
                $data->email = $request->email ?? '';
                $data->zip_code = $request->zip_code ?? '';
                $data->state = getCityState($request->zip_code) ?? '';
                $data->address = $request->address ?? '';
                $data->notes = $request->notes ?? '';
                $data->status = $request->status ?? 'active';
                $data->list_id = $request->list_id ?? '';
                $data->user_id = $request->user_id;
                $ok = $data->save();
                if ($ok) {
                    $resp = [
                        'data' => $data->id,
                        'status' => true,
                        'message' => 'Contact added successfully',
                    ];
                } else {
                    $resp = [
                        'data' => '',
                        'status' => false,
                        'message' => 'Contact added fail',
                    ];
                }
            }
        }
        return response()->json($resp);
    }
    // public function getContacts(Request $request, $user_id = null)
    public function getContacts($user_id = null, $keywords = null)
    {
        $user_id = (int)$user_id > 0 ? (int)$user_id : null;
        $rawWhere = "(1=1)";
        if ($keywords) {
            $rawWhere = "(contactlists.name LIKE '%{$keywords}%' OR contactlists.contact LIKE '%{$keywords}%')";
        }
        if ($user_id) {
            $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])->where(['contactlists.user_id' => $user_id])
                ->whereRaw($rawWhere)
                ->paginate(10);
        } else {
            $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])
                ->whereRaw($rawWhere)
                ->paginate(10);
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => '', 'user_id' => $user_id, 'keywords' => $keywords];
        return response()->json($this->resp);
    }
    public function getAllContacts($user_id = null)
    {
        if ($user_id) {
            $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])->where(['contactlists.user_id' => $user_id])->get();
        } else {
            $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])->get();
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }

    public function getContactsPreviousMonth()
    {
        $previousMonth_date = date("Y-m", strtotime("-1 month"));
        // echo $previousMonth_date ; die;
        $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
            return $q->select('id', 'first_name');
        }])->with(['lists' => function ($q) {
            return $q->select('id', 'name');
        }])->where('contactlists.created_at', 'like', '%' . $previousMonth_date . '%')
            ->get();
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    // public function getContactsByCategory($category_id = null)
    // {
    //     if ($category_id) {
    //         $d = Contactlist::select('*', 'category_id', 'user_id', 'list_id')->with(['user' => function ($q) {
    //             return $q->select('id', 'first_name');
    //         }])->with(['categories' => function ($q) {
    //             return $q->select('id', 'name');
    //         }])->with(['lists' => function ($q) {
    //             return $q->select('id', 'name');
    //         }])->where(['is_deleted' => '0', 'category_id' => $category_id])->get();
    //     } else {
    //         $d = Contactlist::select('*', 'category_id', 'user_id', 'user_id')->with(['user' => function ($q) {
    //             return $q->select('id', 'first_name');
    //         }])->with(['categories' => function ($q) {
    //             return $q->select('id', 'name');
    //         }])->with(['lists' => function ($q) {
    //             return $q->select('id', 'name');
    //         }])->where(['is_deleted' => '0'])->get();
    //     }
    //     $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
    //     return response()->json($this->resp);
    // }
    public function getAllContactsByList($list_id = null)
    {
        if ($list_id) {
            $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])->where(['list_id' => $list_id])->get();
        } else {
            $d = Contactlist::select('*', 'user_id', 'user_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])->get();
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function getContactsByList($list_id = null, $keywords = null)
    {
        $rawWhere = "(1=1)";
        if ($keywords) {
            $rawWhere = "(`name` LIKE '%{$keywords}%' OR `contact` LIKE '%{$keywords}%')";
        }
        // $user_id = (int)$user_id > 0?(int)$user_id:null;
        if ($list_id) {
            $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])->where(['list_id' => $list_id])
                // ->where('name', 'LIKE', "%{$keywords}%")
                ->whereRaw($rawWhere)
                ->paginate(10);
        } else {
            $d = Contactlist::select('*', 'user_id', 'user_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])
                ->where('name', 'LIKE', "%{$keywords}%")
                ->paginate(10);
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function getContactsById($id = null)
    {
        $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
            return $q->select('id', 'first_name');
        }])->with(['lists' => function ($q) {
            return $q->select('id', 'name');
        }])->where(['id' => $id])->get();
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function updateContactlistStatus(Request $request)
    {
        $ok = Contactlist::where(['id' => $request->id])->first();
        if ($ok) {
            $done = $ok->update($request->all());
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Contact updated successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Contact update fail',
                ]);
            }
            // return response()->json($this->resp);
        }
    }
    public function updateContactlist(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'contact' => 'required'
            // 'contact' => 'required|digits_between:10,15|numeric'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);
        } else {
            $ok = Contactlist::where(['id' => $request->id])->first();
            if ($ok) {

                if ($request->filled('zip_code'))
                    $request->request->add(['state' => getCityState($request->zip_code) ?? null]);
                $valid = validate_mobile($request->contact);
                if (!$valid) {
                    return response()->json($resp = [
                        'data' => '',
                        'status' => false,
                        'message' => 'Number not valid',
                    ]);
                } else {
                    $request->request->remove('contact');
                    $request->request->add(['contact' => $valid]);

                    $ok->name = $request->name ?? '';
                    $ok->contact = $request->contact;
                    $ok->email = $request->email ?? '';
                    $ok->zip_code = $request->zip_code ?? '';
                    $ok->state = $request->state ?? '';
                    $ok->address = $request->address ?? '';
                    $ok->notes = $request->notes ?? '';
                    $ok->status = $request->status ?? 'active';
                    $ok->list_id = $request->list_id ?? '';
                    $ok->user_id = $request->user_id;
                    // $ok->save();

                    // $done = $ok->update($request->all());
                    if ($ok->save()) {
                        return response()->json([
                            'data' => '',
                            'status' => true,
                            'message' => 'Contact updated successfully',
                        ]);
                    } else {
                        return response()->json([
                            'data' => '',
                            'status' => false,
                            'message' => 'Contact update fail',
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data not found',
                ]);
            }
        }
    }
    public function destroyContact($id)
    {
        if (Contactlist::find($id)->delete()) {

            $user_id = auth()->user()->id;

            $where = '(`from_id` = ' . $id . ' and `to_id` = ' . $user_id . ') or (`from_id` = ' . $user_id . ' and `to_id` = ' . $id . ')';

            // $where = '(`type` = subscriber and `to_id` = ' . $user_id . ')';
            $cam_to_run = DB::table('ch_messages')->whereRaw($where)->delete();
            $cam_to_run = DB::table('chats')->where(['user_id' => $user_id, 'subscriber_id' => $id])->delete();
            return response()->json([
                'data' => '',
                'status' => true,
                'message' => 'Contact delete successfully',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Contact delete fail',
            ]);
        }
    }
    /*get/update users/subscribers/clients/customers*/
    public function getUsers($id = null)
    {
        $sc_date = date('Y-m-d');
        $user_id = $id;
        if ($id) {
            $d = User::where(['id' => $id, 'user_type' => 'user'])->get();

            $data = SmsLimitCount::where(['user_id' => $user_id])
                ->where('created_at', 'like', '%' . $sc_date . '%')->first();
            $d->totalSent = $data ? $data->sms_count : '0';
        } else {
            $d = User::where(['user_type' => 'user'])->get();
            for ($i = 0; $i < count($d); $i++) {
                $dat = SmsLimitCount::where(['user_id' => $d[$i]->id])
                    ->where('created_at', 'like', '%' . $sc_date . '%')->first();
                $d[$i]->totalSent = $dat ? $dat->sms_count : '0';
            }
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }

    public function getProfile()
    {
        $d = User::where(['id' => Auth::user()->id])->get();
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function changeUserPassword(Request $request)
    {
        $user = User::where(['id' => $request->id])->first();
        if ($user) {
            $done = $user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Password updated successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Password update fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }
    public function updateUser(Request $request)
    {
        $user_id = $request->id;
        $validator = \Validator::make($request->all(), [
            // 'email'     => "required|email|unique:App\Models\User,email,$request->id,id",
            'email' => "required|email|unique:users,email," . $user_id . ",id,deleted_at,NULL",
            'contact_number' => "max:20|unique:users,contact_number," . $user_id . ",id,deleted_at,NULL",
            // 'call_forwarding_number' => "max:20|unique:users,call_forwarding_number,".$user_id.",id,deleted_at,NULL",


        ], ['contact_number.unique' => 'Twilio Number Already been taken']);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ];
            return response()->json($resp);
        } else {
            $valid = validate_mobile($request->contact_number);
            $call_forwarding_number = validate_mobile($request->call_forwarding_number);
            if (!$valid || !$call_forwarding_number) {
                $response = [
                    'data' => '',
                    'status' => false,
                    'message' => (!$valid ? 'Twilio Number not valid' : 'Call forwarding number is not valid'),
                ];
            } else {
                $exist = User::where(['contact_number' => $valid])->where('id', '!=', $request->id)->count();
                if ($exist == 0) {
                    $request->request->remove('contact_number');
                    $request->request->add(['contact_number' => $valid]);
                    $request->request->add(['call_forwarding_number' => $call_forwarding_number]);
                    $ok = User::where(['id' => $request->id, 'is_deleted' => '0'])->first();
                    if ($ok) {
                        if ($request->filled('new_password')) {
                            $request->request->add(['password' => Hash::make($request->new_password)]);
                            $request->request->remove('new_password');
                        }
                        $done = $ok->update($request->all());
                        if ($done) {
                            return response()->json([
                                'data' => '',
                                'status' => true,
                                'message' => 'User updated successfully',
                            ]);
                        } else {
                            return response()->json([
                                'data' => '',
                                'status' => false,
                                'message' => 'User update fail',
                            ]);
                        }
                    } else {
                        return response()->json([
                            'data' => '',
                            'status' => false,
                            'message' => 'Data not found',
                        ]);
                    }
                } else {
                    return response()->json([
                        'data' => '',
                        'status' => false,
                        'message' => 'Number already been taken.',
                    ]);
                }
            }
        }
    }
    public function updateUserStatus(Request $request)
    {
        $user_id = $request->id;

        $user = User::where('id', $user_id)->first();

        if ($user == "") {
            $message = __('message.user_not_found');
            $response = [
                'status' => false,
                // 'data' => $user_resource,
                'data' => '',
                'message' => $message
            ];
            return response()->json($response);
        }
        if ($request->has('status')) {
            $user->status = $request->status;
        }
        $user->save();

        $message = __('message.update_form', ['form' => __('message.status')]);
        $response = [
            'status' => true,
            // 'data' => $user_resource,
            'data' => $request->all(),
            'message' => $message
        ];
        return response()->json($response);
    }
    public function destroyUser($id)
    {
        $ok = User::where(['id' => $id])->first();
        if ($ok) {
            $ok->is_deleted = '1';
            $done = $ok->update();
            User::find($id)->delete();
            if ($done) {
                Lists::where('user_id', $id)->update(['deleted_at' => now()]);
                Contactlist::where('user_id', $id)->update(['deleted_at' => now()]);

                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'User delete successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'User delete fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }
    public function changeDailyTextLimit(Request $request)
    {
        $ok = User::where(['id' => $request->id])->first();
        if ($ok) {
            $ok->daily_text_limit = $request->textLimit;
            $done = $ok->update();
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Daily text limit updated successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'User Update fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }
    /*manage blacklist keywords*/
    public function storeBannedWord(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'phrase' => 'required|string|unique:banned_words,phrase'

        ]);
        if ($validator->fails()) {
            $this->resp = [
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ];
        } else {
            $data = new Banned_words;
            $data->phrase = $request->phrase;
            $data->status = $request->status ?? 'active';
            $ok = $data->save();
            if ($ok) {
                $this->resp = [
                    'data' => $data->id,
                    'status' => true,
                    'message' => 'Phrase added successfully',
                ];
            } else {
                $this->resp = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Phrase add fail',
                ];
            }
        }
        return response()->json($this->resp);
    }
    public function updateBannedWord(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            // 'phrase' => 'required|string|unique:banned_words,phrase,'.$request->id.',id'

        ]);
        if ($validator->fails()) {
            $this->resp = [
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ];
        } else {
            $d = Banned_words::where(['id' => $request->id, 'is_deleted' => '0'])->first();
            if ($d) {
                /*$d->phrase = $request->phrase;
	            $d->status = $request->status;
	            $d = $d->save();*/
                $d->update($request->all());
                $this->resp = [
                    'data' => '',
                    'status' => true,
                    'message' => 'Phrase Updated successfully',
                ];
            } else {
                $this->resp = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Phrase update fail',
                ];
            }
        }
        return response()->json($this->resp);
    }

    public function getBannedWords($id = null)
    {
        if ($id)
            $d = Banned_words::where(['is_deleted' => '0', 'id' => $id])->get();
        else
            $d = Banned_words::where(['is_deleted' => '0'])->get();
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }

    public function destroyBanWords($id)
    {
        $ok = Banned_words::where(['id' => $id, 'is_deleted' => '0'])->first();
        if ($ok) {
            $ok->is_deleted = '1';
            $done = $ok->update();
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Data delete successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data delete fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }
    /****lists module****/
    public function create_list(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            // 'contact' => 'required'
            'name' => 'required|unique:lists,name,NULL,NULL,deleted_at,NULL,user_id,' . auth()->user()->id

        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => false,
                'message' => $validator->errors()->first(),
            ];
        } else {

            $resp = array();
            $data = new Lists;
            $data->category_id = $request->category_id;
            $data->name = $request->name;
            $data->status = $request->status ?? 'active';
            $data->user_id = $request->user_id;
            $ok = $data->save();
            if ($ok) {
                $resp = [
                    'data' => $data->id,
                    'status' => true,
                    'message' => 'Data added successfully',
                ];
            } else {
                $resp = [
                    'data' => '',
                    'status' => false,
                    'message' => 'Data added fail',
                ];
            }
        }
        return response()->json($resp);
    }


    public function allLists($user_id = null)
    {
        if ($user_id) {
            $d = Lists::select('*')->with(['user' => function ($q) {
                return $q->select('id', 'first_name', 'last_name');
            }])->with(['categories' => function ($q) {
                return $q->select('id', 'name');
            }])->where(['user_id' => $user_id])->get();
        } else {

            $d = Lists::select('*')->with(['user' => function ($q) {
                return $q->select('id', 'first_name', 'last_name');
            }])->with(['categories' => function ($q) {
                return $q->select('id', 'name');
            }])->get();
        }
        for ($i = 0; $i < count($d); $i++) {
            $d[$i]->count = $d[$i]->contactlist()->count();
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function lists($user_id = null, $keywords = null)
    {
        $user_id = (int)$user_id > 0 ? (int)$user_id : null;
        $rawWhere = "(1=1)";
        if ($keywords) {
            $rawWhere = "(lists.name LIKE '%{$keywords}%')";
        }
        if ($user_id) {
            $d = Lists::select('*')->with(['user' => function ($q) {
                return $q->select('id', 'first_name', 'last_name');
            }])->with(['categories' => function ($q) {
                return $q->select('id', 'name');
            }])->where(['user_id' => $user_id])->whereRaw($rawWhere)->paginate(10);
        } else {

            $d = Lists::select('*')->with(['user' => function ($q) {
                return $q->select('id', 'first_name', 'last_name');
            }])->with(['categories' => function ($q) {
                return $q->select('id', 'name');
            }])->whereRaw($rawWhere)->paginate(10);
        }
        for ($i = 0; $i < count($d); $i++) {
            $d[$i]->count = $d[$i]->contactlist()->count();
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }

    public function listsById($id = null)
    {
        $d = Lists::select('*')->with(['user' => function ($q) {
            return $q->select('id', 'first_name', 'last_name');
        }])->with(['categories' => function ($q) {
            return $q->select('id', 'name');
        }])->where(['id' => $id])->get();
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function updateList(Request $request)
    {
        $ok = Lists::where(['id' => $request->id])->first();
        // return response()->json($ok);
        if ($ok) {
            $done = $ok->update($request->all());
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Data updated successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data update fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }
    public function destroyList($id)
    {
        if (Lists::find($id)->delete()) {
            Contactlist::where('list_id', $id)->update(['deleted_at' => now()]);
            return response()->json([
                'data' => '',
                'status' => true,
                'message' => 'Data delete successfully',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data delete fail',
            ]);
        }
    }
    /*destroyList*/

    /*manage Campaign*/
    public function campaigns($user_id = null)
    {
        if ($user_id) {
            $d = Campaigns::select('*')->with(['user' => function ($q) {
                return $q->select('id', 'first_name', 'last_name');
            }])->where(['user_id' => $user_id,])->get();
        } else {

            $d = Campaigns::select('*')->with(['user' => function ($q) {
                return $q->select('id', 'first_name', 'last_name');
            }])->get();
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }
    public function campaignById($id)
    {
        $d = Campaigns::where(['id' => $id])->first();
        if ($d) {

            $d->tags = ($d->tags ? explode(',', $d->tags) : array());
            $d->campaign_send_to_emails = ($d->campaign_send_to_emails ? explode(',', $d->campaign_send_to_emails) : array());
            $d->campaign_not_send_to_emails = ($d->campaign_not_send_to_emails ? explode(',', $d->campaign_not_send_to_emails) : array());
            $d->campaign_send_to_list_ids = ($d->campaign_send_to_list_ids ? explode(',', $d->campaign_send_to_list_ids) : array());
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            $this->resp = [
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ];
        }
        return response()->json($this->resp);
    }

    public function runCampaignNow($id = '')
    {
        $cam_to_run = DB::table('campaign_details')
            ->join('campaigns', 'campaign_details.campaign_id', '=', 'campaigns.id')
            ->select('campaign_details.*', 'campaigns.id AS campaigns_id', 'campaigns.message', 'campaigns.title', 'campaigns.user_id', 'campaigns.campaign_send_to_emails', 'campaigns.campaign_not_send_to_emails', 'campaigns.campaign_send_to_list_ids')
            ->where('campaign_details.id', $id)
            ->first();
        if ($cam_to_run) {
            $statusData = ['status' => 'success'];
            $do = sendSMS($cam_to_run);
            if ($do != 'limit') {
                $statusData = ['status' => 'success'];
            } else {
                $statusData = ['status' => 'cancel'];
            }
            DB::table('campaign_details')->where('id', $cam_to_run->id)->update($statusData);
            DB::table('campaigns')->where('id', $cam_to_run->campaigns_id)->update($statusData);

            return $do;
        }
    }
    public function create_campaigns(Request $request)
    {
        ini_set('max_execution_time', '600');
        $resp = array();
        // DB::beginTransaction();
        try {
            $tags = NULL;
            $campaign_send_to_emails = NULL;
            $campaign_not_send_to_emails = NULL;
            $campaign_send_to_list_ids = NULL;
            if ($request->has('tags')) {
                $tags = implode(',', $request->tags);
            }
            if ($request->has('campaign_send_to_emails')) {
                $campaign_send_to_emails = implode(',', $request->campaign_send_to_emails);
            }
            if ($request->has('campaign_not_send_to_emails')) {
                $campaign_not_send_to_emails = implode(',', $request->campaign_not_send_to_emails);
            }
            if ($request->has('campaign_send_to_list_ids')) {
                $campaign_send_to_list_ids = implode(',', $request->campaign_send_to_list_ids);
            }
            $status = 'pending';
            if ($request->send_now == 'true') {
                $status = 'success';
            }
            $data = new Campaigns;
            $data->title = $request->title;
            $data->type = $request->type; //sms OR email
            $data->status = 'pending'; //pending, success or cancel
            $data->campaign_interval = $request->campaign_interval ?? 'daily'; //it may be daily, weekly, monthly or annually 
            $data->send_now = $request->send_now; //it may be true or false 
            $data->campaign_start_time = $request->campaign_start_time; //when to run send i.e. 10:00, 16:30, etc. 
            $data->campaign_start_date = $request->campaign_start_date; //from which date campaign has to be run
            $data->campaign_end_date = $request->campaign_end_date; //campaign end date
            $data->campaign_time_zone = $request->campaign_time_zone; //according to which time zone campaign has to be run
            $data->tags = $tags; //comma separated tags
            $data->subject_line = $request->subject_line; // for email campaign
            $data->preview_text = $request->preview_text; // for email campaign
            $data->message = $request->message; // formated address
            $data->sender_name = $request->sender_name; //for email campaign
            $data->sender_email = $request->sender_email ?? ''; // for email campaign
            $data->user_id = $request->user_id;
            $data->sender_email_as_reply_to = $request->sender_email_as_reply_to ?? 'no'; // for email campaign
            $data->campaign_send_to_emails = $campaign_send_to_emails;
            $data->campaign_send_to_list_ids = $campaign_send_to_list_ids;
            $data->campaign_not_send_to_emails = $campaign_not_send_to_emails;
            $data->save();

            if ($request->send_now == 'true') {
                $details = array('campaign_id' => $data->id, 'status' => 'pending', 'schedule_date' => $request->campaign_start_date . ' ' . $request->campaign_start_time);
                Campaign_detail::insert($details);
                $last_id = DB::getPdo()->lastInsertId();
                $ok = $this->runCampaignNow($last_id);
                if ($ok == 'limit') {
                    $resp = [
                        'data' => $data->id,
                        // 'detail' => $ok,
                        'status' => true,
                        'message' => 'Campaign is created but not running as your daily text limit exceeded',
                    ];
                } else {
                    $resp = [
                        'data' => $data->id,
                        // 'detail' => $ok,
                        'status' => true,
                        'message' => 'Data added successfully',
                    ];
                }
            } else {
                $campaign_start_date = $request->campaign_start_date;
                $campaign_end_date = $request->campaign_end_date;
                if ($request->campaign_interval == 'one time') {
                    $campaign_end_date = $campaign_start_date;
                }

                $period = CarbonPeriod::create($campaign_start_date, $campaign_end_date);

                $dates = [];
                $mult = 0;
                foreach ($period as $date) {

                    if ($mult == 0) {
                        $d = $date->format('Y-m-d');
                    } else {

                        if ($request->campaign_interval == 'weekly') {
                            $d = date('Y-m-d', strtotime("+1 week", strtotime($dates[$mult - 1])));
                        } elseif ($request->campaign_interval == 'monthly') {
                            $d = date('Y-m-d', strtotime("+1 month", strtotime($dates[$mult - 1])));
                        } elseif ($request->campaign_interval == 'annually') {
                            $d = date('Y-m-d', strtotime("+1 year", strtotime($dates[$mult - 1])));
                        } else {
                            $d = $date->format('Y-m-d');
                        }
                    }
                    if ($d > $campaign_end_date)
                        break;
                    $dates[] = $d;
                    $mult++;
                }
                foreach ($dates as $dkey) {
                    $details[] = array('campaign_id' => $data->id, 'status' => 'pending', 'schedule_date' => $dkey . ' ' . $request->campaign_start_time);
                }
                Campaign_detail::insert($details);
                $resp = [
                    'data' => $data->id,
                    // 'detail' => $ok,
                    'status' => true,
                    'message' => 'Data added successfully',
                ];
            }

            // DB::commit();

        } catch (Exception $e) {
            // DB::rollback();
            $resp = [
                'data' => '',
                'status' => false,
                'message' => 'Data added fail',
            ];
        }
        return response()->json($resp);
    }
    public function update_campaign(Request $request)
    {
        $ok = Campaigns::where(['id' => $request->id])->first();
        if ($ok) {

            DB::beginTransaction();
            try {
                $tags = NULL;
                $campaign_send_to_emails = NULL;
                $campaign_not_send_to_emails = NULL;
                $campaign_send_to_list_ids = NULL;
                if ($request->has('tags')) {
                    $tags = implode(',', $request->tags);
                }
                if ($request->has('campaign_send_to_emails')) {
                    $campaign_send_to_emails = implode(',', $request->campaign_send_to_emails);
                }
                if ($request->has('campaign_not_send_to_emails')) {
                    $campaign_not_send_to_emails = implode(',', $request->campaign_not_send_to_emails);
                }
                if ($request->has('campaign_send_to_list_ids')) {
                    $campaign_send_to_list_ids = implode(',', $request->campaign_send_to_list_ids);
                }
                // $data = new Campaigns;
                $data = array();
                if ($request->has('title'))
                    $data['title'] = $request->title;
                if ($request->has('type'))
                    $data['type'] = $request->type; //sms OR email
                if ($request->has('status'))
                    $data['status'] = $request->status ?? 'pending'; //pending, success or cancel
                if ($request->has('campaign_interval'))
                    $data['campaign_interval'] = $request->campaign_interval ?? 'daily'; //it may be daily, weekly, monthly or annually 
                if ($request->has('send_now'))
                    $data['send_now'] = $request->send_now; //it may be daily, weekly, monthly or annually 
                if ($request->has('campaign_start_time'))
                    $data['campaign_start_time'] = $request->campaign_start_time; //when to run send i.e. 10:00, 16:30, etc. 
                if ($request->has('campaign_start_date'))
                    $data['campaign_start_date'] = $request->campaign_start_date; //from which date campaign has to be run
                if ($request->has('campaign_end_date'))
                    $data['campaign_end_date'] = $request->campaign_end_date; //campaign end date
                if ($request->has('campaign_time_zone'))
                    $data['campaign_time_zone'] = $request->campaign_time_zone; //according to which time zone campaign has to be run
                if ($request->has('tags'))
                    $data['tags'] = $tags; //comma separated tags
                if ($request->has('subject_line'))
                    $data['subject_line'] = $request->subject_line; // for email campaign
                if ($request->has('preview_text'))
                    $data['preview_text'] = $request->preview_text; // for email campaign
                if ($request->has('message'))
                    $data['message'] = $request->message; // formated address
                if ($request->has('sender_name'))
                    $data['sender_name'] = $request->sender_name; //for email campaign
                if ($request->has('sender_email'))
                    $data['sender_email'] = $request->sender_email; // for email campaign

                if ($request->has('user_id'))
                    $data['user_id'] = $request->user_id;
                if ($request->has('sender_email_as_reply_to'))
                    $data['sender_email_as_reply_to'] = $request->sender_email_as_reply_to ?? 'no'; // for email campaign
                if ($request->has('campaign_send_to_emails'))
                    $data['campaign_send_to_emails'] = $campaign_send_to_emails;
                if ($request->has('campaign_send_to_list_ids'))
                    $data['campaign_send_to_list_ids'] = $campaign_send_to_list_ids;
                if ($request->has('campaign_not_send_to_emails'))
                    $data['campaign_not_send_to_emails'] = $campaign_not_send_to_emails;

                $done = Campaigns::where('id', $ok->id)->update($data);

                // if($done){
                if ($request->has('send_now') || $request->has('campaign_interval') || $request->has('campaign_start_date') || $request->has('campaign_end_date')) {

                    Campaign_detail::where('campaign_id', $ok->id)->delete();
                    if ($request->send_now && $request->send_now == 'true') {
                        $details = array('campaign_id' => $ok->id, 'status' => 'success', 'schedule_date' => $request->campaign_start_date . ' ' . $request->campaign_start_time);
                        $last_id = DB::getPdo()->lastInsertId();
                        if ($ok->send_now == 'false') { //if campaign already not done then it will run 
                            $this->runCampaignNow($last_id);
                        }
                    } else {
                        $start_date = $request->campaign_start_date;
                        $end_date = $request->campaign_end_date;
                        $interval = $request->campaign_interval;
                        if ($interval == 'one time')
                            $end_date = $request->campaign_start_date;

                        $period = CarbonPeriod::create($start_date, $end_date);

                        $dates = [];
                        $mult = 0;
                        foreach ($period as $date) {

                            if ($mult == 0) {
                                $d = $date->format('Y-m-d');
                            } else {

                                if ($interval == 'weekly') {
                                    $d = date('Y-m-d', strtotime("+1 week", strtotime($dates[$mult - 1])));
                                } elseif ($interval == 'monthly') {
                                    $d = date('Y-m-d', strtotime("+1 month", strtotime($dates[$mult - 1])));
                                } elseif ($interval == 'annually') {
                                    $d = date('Y-m-d', strtotime("+1 year", strtotime($dates[$mult - 1])));
                                } else {
                                    $d = $date->format('Y-m-d');
                                }
                            }
                            if ($d > $end_date)
                                break;
                            $dates[] = $d;
                            $mult++;
                        }
                        foreach ($dates as $dkey) {
                            $details[] = array('campaign_id' => $ok->id, 'status' => 'pending', 'schedule_date' => $dkey . ' ' . ($request->campaign_start_time ?? $ok->campaign_start_time));
                        }


                        Campaign_detail::insert($details);
                    }
                }

                DB::commit();

                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Data updated successfully',
                ]);
            } catch (Exception $e) {
                DB::rollback();
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data added fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }
    /*manage Campaign*/

    /*App Settings*/

    public function getAppSetting($id = 1)
    {
        $d = AppSetting::getData();
        if ($d) {
            $this->resp = [
                'data' => $d,
                'status' => true,
                'message' => '',
            ];
        } else {
            $this->resp = [
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ];
        }
        return response()->json($this->resp);
    }

    public function update_app_settings(Request $request)
    {
        $ok = AppSetting::getData();
        if ($ok) {
            $done = $ok->update($request->all());
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Data updated successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Data update fail',
                ]);
            }
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'Data not found',
            ]);
        }
    }
}
