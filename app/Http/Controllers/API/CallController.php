<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use Twilio\TwiML\VoiceResponse;

class CallController extends Controller
{


    public function newCall(Request $request)
    {
        twilioVoiceCalls();

        /*$response = new VoiceResponse();
        $callerIdNumber = config('services.twilio')['number'];

        $dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
        $phoneNumberToDial = '+13477194581';

        if (isset($phoneNumberToDial)) {
            $dial->number($phoneNumberToDial);
        } else {
            $dial->client('support_agent');
        }

        return $response;*/
    }
}