<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Genneral_chat;
use App\Models\User;
// use App\Events\MessageSent;
use Pusher;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resp = array();
        $d = Genneral_chat::select('*')->with(['user' => function($q){
                    return $q->select('id','first_name','last_name');
                }])->where(['is_deleted'=>'0'])->get();
        if($d){
            $resp = [
                'data' => $d,
                'status'=>true, 
                'message'=>'', 
            ];
        }else{
            $resp = [
                'data' => '',
                'status'=>true, 
                'message'=>'No Data found', 
            ];
        }
        return response()->json($resp);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
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
            'message' => 'required'
           
        ]);
        if ($validator->fails()) {
            $resp = [
                'data' => '',
                'status'=>false, 
                'message'=>$validator->errors(), 
            ];
        }
        else{
            $resp = array();
        
            $data = new Genneral_chat;
            $data->message = $request->message;
            $data->sender_id = auth()->user()->id;
            $ok = $data->save();
            if($ok){
                // $user = User::where('id', auth()->user()->id)->first();
                // echo '<pre>'; print_r('$pusher'); die;
                $pusher = new Pusher\Pusher(
                env("PUSHER_APP_KEY"),
                env("PUSHER_APP_SECRET"),
                env("PUSHER_APP_ID"),
                array('cluster' => env('PUSHER_APP_CLUSTER'))
                
                );
                // echo '<pre>'; print_r($pusher); die;
                $pusher->trigger('general-chat', 'client-general-event', array('message' => '$request->message'));
                
                $resp = [
                    'data' => $request->message,
                    'status'=>true, 
                    'message'=>'Data Saved successfully', 
                ];
            }
            else{
                $resp = [
                    'data' => '',
                    'status'=>false, 
                    'message'=>'Data added fail', 
                ];
            }
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
        //
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
