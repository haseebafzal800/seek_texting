<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;

use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;

class TokenController extends Controller
{
    public $accessToken;
    public function __construct(AccessToken $accessToken)
    {
        $this->accessToken=$accessToken;
    }/**
     * Create a new capability token
     *
     * @return \Illuminate\Http\Response
     */
    public function newToken(Request $request)
    {

        $data = file_get_contents('https://ruby-ape-3928.twil.io/capability-token');
        $decoded = json_decode($data);
        // var_dump($decoded->token);

        return response()->json(
            [
                'status' => true,
                'data' => $decoded->token,
                'message' => '',
            ]
        );
    }
}