<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contactlist;
use App\Models\Chat;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ImportContactlist;
use App\Exports\ContactlistExport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use DB;
use App\Jobs\ProcessImportJob;
use App\Jobs\ProcessImportJobs;
// use Artisan;
// use Illuminate\Support\Facades\Input;

class ContactlistController extends Controller
{
    public $resp;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function unsubscribeRates()
    {
        $previousMonth_date = dateBeforeOneMonth();
        $currentMonth_date = date("Y-m-d") . ' 23:59:59';
        if (auth()->user()->user_type == 'user') {
            $overall = Contactlist::where(['status' => 'de-active', 'user_id' => auth()->user()->id])->count();
        } else {
            $overall = Contactlist::where('status', 'de-active')->count();
        }

        $avg = $overall / 30;
        $resp = [
            'status' => true,
            'data' => [
                'total_unsubscribe' => $overall,
                'avg_pr_day' => round($avg, 2),
            ],
            'message' => ''

        ];
        return response()->json($resp);
    }
    public function getContactsPreviousMonth()
    {
        $previousMonth_date = date("Y-m", strtotime("-1 month"));
        // echo $previousMonth_date ; die;
        $d = Contactlist::select('*', 'list_id')->with(['lists' => function ($q) {
            return $q->select('id', 'name');
        }])->where(['user_id' => auth()->user()->id])
            ->where('contactlists.created_at', 'like', '%' . $previousMonth_date . '%')
            ->get();

        $resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($resp);
    }
    public function index($keywords = null)
    {
        // DB::connection()->enableQueryLog();
        $rawWhere = "(1=1)";
        if ($keywords) {
            $rawWhere = "(`name` LIKE '%{$keywords}%' OR `contact` LIKE '%{$keywords}%')";
        }

        
        $d = Contactlist::select('*')->with(['lists' => function ($q) {
            return $q->select('id', 'name');
        }])
            ->where(['user_id' => auth()->user()->id])
            ->whereRaw($rawWhere)
            ->paginate(10);


        // $queries = DB::getQueryLog();

        // $last_query = end($queries);
        if ($d) {
            $resp = [
                'data' => $d,
                'status' => true,
                'message' => '',
                'keywords' => $keywords,
            ];
        } else {
            $resp = [
                'data' => '',
                'status' => true,
                'message' => 'No Data found',
                'keywords' => $keywords,
            ];
        }
        return response()->json($resp);
    }

    public function getAllContacts()
    {
        $d = Contactlist::select('*')->with(['lists' => function ($q) {
            return $q->select('id', 'name');
        }])->where(['user_id' => auth()->user()->id])->get();
        if ($d) {
            $resp = [
                'data' => $d,
                'status' => true,
                'message' => '',
            ];
        } else {
            $resp = [
                'data' => '',
                'status' => true,
                'message' => 'No Data found',
            ];
        }
        return response()->json($resp);
    }
    /*public function index()
{
    try {
        $contactlist = Contactlist::where('user_id', auth()->user()->id)->get();
        $last_query = DB::getQueryLog();
        $data = [
            'contactlist' => $contactlist,
            'last_query' => end($last_query),
        ];
        $status = true;
        $message = '';
    } catch (Exception $e) {
        Log::error('Error fetching contactlist: ' . $e->getMessage());
        $data = [];
        $status = false;
        $message = 'Error fetching contactlist. Please try again later.';
    }
    
    $response = [
        'data' => $data,
        'status' => $status,
        'message' => $message,
    ];
    
    return response()->json($response);
}*/
    public function import(Request $request)
    {
        // set_time_limit(0);
        ini_set('max_execution_time', '600');
        if ($request->file && $request->file->isValid()) {
            $file = $request->file('file');
            $handle = fopen($file, "r");
            $header1 = fgetcsv($handle, 0, ',');
            $header = array_map('trim', $header1);
            // $countheader= count($header);

            if (in_array('Name', $header) && in_array('Contact', $header) && in_array('Email', $header) && in_array('Zip Code', $header) && in_array('Address', $header)) {
                $ok = Excel::import(new ImportContactlist($request->id), $request->file('file')->store('files'));
                $this->resp = ['data' => '', 'status' => true, 'message' => 'Data Imported successfully'];
            } else {
                $this->resp = ['data' => '', 'status' => false, 'message' => 'Invalid File Type '];
            }
        } else {
            $this->resp = ['data' => '', 'status' => false, 'message' => 'File is Invalid '];
        }
        return response()->json($this->resp);
    }
    public function upload_csv_file(Request $request)
    {
        if ($request->file) {
            try {
                $file = $request->file('file');
                $name = base_path('app/import') . '/' . $file->getClientOriginalName();
                $file->move(base_path('app/import'), $file->getClientOriginalName());

                $listId = $request->list_id; // Assign the value to a variable

                if ($listId) {
                    $ok = ProcessImportJob::dispatch($name, $listId, $request->user_id)->delay(10);
                    // print_r($ok);

                    $resp = [
                        'status' => true,
                        'message' => 'Process in queue. Contacts will be uploaded shortly.'
                    ];

                    return response()->json($resp);
                } else {
                    $resp = [
                        'status' => false,
                        'message' => 'Please provide a valid list ID.'
                    ];
                    // Artisan::call('queue:work --stop-when-empty');
                    return response()->json($resp);
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                // Log the error message and get the log file path
                \Log::error('Error in upload_csv_file: ' . $error_message);
                $log_file_path = storage_path('logs/laravel.log');

                $resp = [
                    'status' => false,
                    'message' => 'An error occurred during file upload. Please try again later.',
                    'log_file' => $log_file_path
                ];

                return response()->json($resp);
            }
        }

        // Handle the case where no file was uploaded
        $resp = [
            'status' => false,
            'message' => 'Please upload a CSV file.'
        ];

        return response()->json($resp);
    }
    public function export(Request $request)
    {
        $filename = 'contactlist_' . date('Y_m_d_His') . '.csv';
        // return Excel::download(new ContactlistExport($request->id), $filename);
        $ok = Excel::store(new ContactlistExport($request->id), $filename, 'export');
        if ($ok) {
            $path = asset('app/export') . '/' . $filename;
            $this->resp = ['data' => $path, 'status' => true, 'message' => ''];
        } else {
            $this->resp = ['data' => '', 'status' => false, 'message' => 'Something went wrong please try again'];
        }
        return response()->json($this->resp);
    }
    public function validateMobile($value = '(407) 744-4485')
    {
        $a = validate_mobile($value);
        // if(validate_mobile($value))
        var_dump($a);
        return response()->json($a);
    }
    public function getContactsByList($list_id = null)
    {
        if ($list_id) {
            $d = Contactlist::select('*', 'user_id', 'list_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])->where(['list_id' => $list_id])->paginate(10);
        } else {
            $d = Contactlist::select('*', 'user_id', 'user_id')->with(['user' => function ($q) {
                return $q->select('id', 'first_name');
            }])->with(['lists' => function ($q) {
                return $q->select('id', 'name');
            }])->paginate(10);
        }
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }

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
    public function searchContactList(Request $request)
    {
        $db = new Contactlist;
        // dd($request->all());
        if ($request->type == 'email')
            $db = $db->select('name', 'email');
        else
            $db = $db->select('name', 'contact');
        $db = $db->where(['status' => 'active']);
        if ($request->has('keywords')) {
            $db = $db->where('name', 'like', '%' . $request->keywords . '%');
            $db = $db->orWhere('contact', 'like', '%' . $request->keywords . '%');
            $db = $db->orWhere('email', 'like', '%' . $request->keywords . '%');
        }
        // $d = $db->toSql();
        $d = $db->get();
        $this->resp = ['data' => $d, 'status' => true, 'message' => ''];
        return response()->json($this->resp);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'contact' => 'required'
            // 'contact' => 'required|digits_between:10,15|numeric'

        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => true,
                'message' => $validator->errors(),
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
                $exist = Contactlist::where('contact', $valid)->first();
                if (!$exist || !$exist->id) {
                    $resp = array();
                    // $s = getCityState($request->zip_code);
                    $data = new Contactlist;
                    $data->name = $request->name ?? '';
                    $data->contact = $valid;
                    $data->email = $request->email ?? '';
                    $data->zip_code = $request->zip_code ?? '';
                    $data->state = getCityState($request->zip_code) ?? '';
                    $data->address = $request->address ?? '';
                    $data->notes = $request->notes ?? '';
                    $data->status = $request->status ?? 'active';
                    $data->list_id = $request->list_id;
                    $data->user_id = auth()->user()->id;
                    // echo('<pre>');
                    // print_r($data);
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
                } else {
                    $resp = [
                        'data' => '',
                        'status' => false,
                        'message' => 'Contact number already been taken',
                    ];
                }
            }
        }
        return response()->json($resp);
    }

    public function storeNewContact(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'contact' => 'required'
            // 'contact' => 'required|digits_between:10,15|numeric'

        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => true,
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
                // DB::connection()->enableQueryLog();


                $eok = Contactlist::where(['contact' => $valid, 'user_id' => auth()->user()->id])->count();
                // $queries = DB::getQueryLog();

                // $last_query = end($queries);
                // return response()->json($last_query);
                if ($eok == 0) {
                    $resp = array();
                    // $s = getCityState($request->zip_code);
                    $data = new Contactlist;
                    $data->name = $request->name ?? '';
                    $data->contact = $valid;
                    $data->email = $request->email ?? '';
                    $data->zip_code = $request->zip_code ?? '';
                    $data->state = getCityState($request->zip_code) ?? '';
                    $data->address = $request->address ?? '';
                    $data->notes = $request->notes ?? '';
                    $data->status = $request->status ?? 'active';
                    $data->list_id = $request->list_id;
                    $data->user_id = auth()->user()->id;

                    $ok = $data->save();
                    $new_user_id = $data->id;
                    $msg = 'success! Contact added';
                } else {
                    $eok1 = Contactlist::where(['contact' => $valid, 'user_id' => auth()->user()->id])->first();
                    // $eok1->created_at = 
                    $new_user_id = $eok1->id;
                    $msg = 'Contact already exists';
                }
                $y = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $new_user_id])->count();
                if ($y < 1) {
                    $dataChat = array(
                        'user_id' => auth()->user()->id,
                        'owner' => 'user', //user means our user and subscriber means 
                        'subscriber_id' => $new_user_id,
                        // 'created_at' => $todates,
                    );
                    Chat::insert($dataChat);
                } else {
                    $yc = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $new_user_id])->first();
                    $yc->created_at = date('Y-m-d H:i:s'); //2023-05-12 13:08:48
                    $yc->update();
                }
                // var_dump($new_user_id);
                $chats = DB::table('contactlists')
                    ->join('chats', 'chats.subscriber_id', '=', 'contactlists.id')
                    ->join('users', 'chats.user_id', '=', 'users.id')
                    ->select('contactlists.*')
                    ->where(['chats.user_id' => auth()->user()->id])
                    ->orderBy('chats.created_at', 'DESC')
                    ->orderBy('chats.updated_at', 'DESC')
                    ->paginate(50);
                // ->get();
                // $contact_list = DB::table('contactlists')->where(['user_id' => auth()->user()->id])->get();
                $return = array();
                for ($c = 0; $c < count($chats); $c++) {
                    $where = '(`from_id` = ' . $chats[$c]->id . ' and `to_id` = ' . auth()->user()->id . ')';
                    $cam_to_run = DB::table('ch_messages')->select('*')
                        ->whereRaw($where)->where('seen', '0')
                        ->count();
                    $chats[$c]->unread = $cam_to_run;
                }
                /*for ($c = 0; $c < count($contact_list); $c++) {
                    $where = '(`from_id` = ' . $contact_list[$c]->id . ' and `to_id` = ' . auth()->user()->id . ') or (`from_id` = ' . auth()->user()->id . ' and `to_id` = ' . $contact_list[$c]->id . ')';
                    $cam_to_run = DB::table('ch_messages')->select('*')
                        ->whereRaw($where)
                        ->first();

                    if ($cam_to_run) {
                        $return[] = $contact_list[$c];
                    }
                    if ($new_user_id == $contact_list[$c]->id)
                        $return[] = $contact_list[$c];
                }*/
                $resp = [
                    'status' => true,
                    'message' => 'Success! Chat created',
                    'data' => $chats,
                    'sort' => 'asc'
                ];
            }
        }
        return response()->json($resp);
    }

    public function createNewChat(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'id' => 'required'

        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status' => true,
                'message' => $validator->errors()->first(),
            ];
        } else {

            // $eok1 = Contactlist::where(['id' => $request->id, 'user_id' => auth()->user()->id])->first();
            // $new_user_id = $eok1->id;
            $y = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $request->id])->count();
            if ($y < 1) {
                $dataChat = array(
                    'user_id' => auth()->user()->id,
                    'owner' => auth()->user()->user_type, //user means our user and subscriber means 
                    'subscriber_id' => $request->id,
                );
                Chat::insert($dataChat);
            } else {
                $yc = Chat::where(['user_id' => auth()->user()->id, 'subscriber_id' => $request->id])->first();
                $yc->created_at = date('Y-m-d H:i:s'); //2023-05-12 13:08:48
                $yc->update();
            }

            $chats = DB::table('contactlists')
                ->join('chats', 'chats.subscriber_id', '=', 'contactlists.id')
                ->join('users', 'chats.user_id', '=', 'users.id')
                ->select('contactlists.*')
                ->where(['chats.user_id' => auth()->user()->id])
                ->orderBy('chats.created_at', 'DESC')
                ->orderBy('chats.updated_at', 'DESC')
                ->paginate(50);
            // $contact_list = DB::table('contactlists')->where(['user_id' => auth()->user()->id])->get();
            $return = array();
            for ($c = 0; $c < count($chats); $c++) {
                $where = '(`from_id` = ' . $chats[$c]->id . ' and `to_id` = ' . auth()->user()->id . ')';
                $cam_to_run = DB::table('ch_messages')->select('*')
                    ->whereRaw($where)->where('seen', '0')
                    ->count();
                $chats[$c]->unread = $cam_to_run;
            }

            $resp = [
                'status' => true,
                'message' => 'Success! Chat created',
                'data' => $chats,
            ];
        }
        return response()->json($resp);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $d = Contactlist::select('*')->with(['lists' => function ($q) {
            return $q->select('id', 'name');
        }])->where(['id' => $id, 'user_id' => auth()->user()->id])->first();
        if ($d) {
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ]);
        }
    }
    // public function getByCategory($category_id)
    // {
    //     $d = Contactlist::select('*', 'category_id', 'list_id')->with(['lists' => function ($q) {
    //         return $q->select('id', 'name');
    //     }])->where(['category_id' => $category_id, 'user_id' => auth()->user()->id])->get();
    //     if ($d) {
    //         return response()->json([
    //             'data' => $d,
    //             'status' => true,
    //             'message' => '',
    //         ]);
    //     } else {
    //         return response()->json([
    //             'data' => '',
    //             'status' => false,
    //             'message' => 'data not found',
    //         ]);
    //     }
    // }
    // public function getByList($list_id, $keywords = null)
    // {
    //     $rawWhere = "(1=1)";
    //     if ($keywords) {
    //         $rawWhere = "(`contactlists`.`name` LIKE '%{$keywords}%' OR `contactlists`.`contact` LIKE '%{$keywords}%')";
    //     }
    //     $d = Contactlist::select('*')->with(['lists' => function ($q) {
    //         return $q->select('id', 'name');
    //     }])
    //         ->where(['list_id' => $list_id, 'user_id' => auth()->user()->id])
    //         ->whereRaw($rawWhere)
    //         ->paginate(10);
    //     if ($d) {
    //         return response()->json([
    //             'data' => $d,
    //             'status' => true,
    //             'message' => '',
    //         ]);
    //     } else {
    //         return response()->json([
    //             'data' => '',
    //             'status' => false,
    //             'message' => 'data not found',
    //         ]);
    //     }
    // }
    public function getByList($list_id, $keywords=null)
    {
        $rawWhere = "(1=1)";
        if ($keywords) {
            $rawWhere = "(`contactlists`.`name` LIKE '%{$keywords}%' OR `contactlists`.`contact` LIKE '%{$keywords}%')";
        }
        $d = Contactlist::select('*')->with(['lists' => function ($q) {
            return $q->select('id', 'name');
        }])
            ->where(['list_id' => $list_id, 'user_id' => auth()->user()->id])
            ->whereRaw($rawWhere)
            ->paginate(10);
        if ($d) {
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ]);
        }
        
       
    }
    public function getAllByList($list_id)
    {
        $d = Contactlist::select('*')->with(['lists' => function ($q) {
            return $q->select('id', 'name');
        }])->where(['list_id' => $list_id, 'user_id' => auth()->user()->id])->get();
        if ($d) {
            return response()->json([
                'data' => $d,
                'status' => true,
                'message' => '',
            ]);
        } else {
            return response()->json([
                'data' => '',
                'status' => false,
                'message' => 'data not found',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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
    public function update(Request $request)
    {
        $ok = Contactlist::where(['id' => $request->id, 'user_id' => auth()->user()->id])->first();
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
                $ok->user_id = auth()->user()->id;
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
    public function getState(Request $request)
    {
        $state = getCityState($request->zip_code);
        return response()->json([
            'data' => $state,
            'status' => true,
            'message' => '',
        ]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
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
}
