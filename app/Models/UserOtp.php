<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Twilio\Rest\Client;
use Exception;
class UserOtp extends Model
{
    use HasFactory;
    protected $guarded =[''];
    public function sendSMS($receiverNumber)
    {
        $message = "Login OTP is ".$this->otp;
    
        try {
  
            // $account_sid = "AC0e9de838f1a9c0fdea308d4f2a16f92a";
            // $auth_token = "025cd6ec820c2efb5012b99a2e5f050a";
            // $twilio_number = "+14694253422";

            $account_sid = "ACfeab3f10c3b93752910c01d12a458811";
            $auth_token = "730d2b6fb96e88a918a7602ede3669cd";
            $twilio_number = "+18452539498";

            $client = new Client($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number, 
                'body' => $message]);
            info('SMS Sent Successfully.');
    
        } catch (Exception $e) {
            info("Error: ". $e->getMessage());
        }
    }
}
