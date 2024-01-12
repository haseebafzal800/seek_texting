<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notifications;


class NotificationsController extends Controller
{
    //
    
    public function index()
    {
        $d['notifications'] = Notifications::where(['user_id' => auth()->user()->id])->orderBy('id', 'desc')->get();
        $d['total_unread'] = Notifications::where(['user_id' => auth()->user()->id, 'seen'=>'0'])->count();
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
    
    public function update(Request $request)
    {
        // $ok = Notifications::where(['user_id' => auth()->user()->id])->first();
        // if ($ok) {
            // $ok->seen = '1';
            $done = Notifications::where(['user_id' => auth()->user()->id, 'seen'=>'0'])->update(['seen'=>'1']);
            if ($done) {
                return response()->json([
                    'data' => '',
                    'status' => true,
                    'message' => 'Notification updated successfully',
                ]);
            } else {
                return response()->json([
                    'data' => '',
                    'status' => false,
                    'message' => 'Notification update fail',
                ]);
            }
        // } else {
        //     return response()->json([
        //         'data' => '',
        //         'status' => false,
        //         'message' => 'Notification not found',
        //     ]);
        // }
    }
}
