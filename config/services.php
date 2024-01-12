<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'rest_api_key' => env('ONESIGNAL_REST_API_KEY')
    ],
      'twilio' => [
        'accountSid' => env('TWILIO_SID'),
        'apiKey' => env('TWILIO_API_KEY'),
        'apiSecret' => env('TWILIO_API_SECRET'),
        'applicationSid' => env('WILIO_APPLICATION_SID'),
        'number' => env('TWILIO_FROM'),
    ],
      /*'twilio' => [
        'accountSid' => 'AC4141f4ff7f2c0b63fb82d53f63617e42',
        'apiKey' => 'SK89a98f260a2e7743a58adc93e25882b1',
        'apiSecret' => 'ChjCDFWfFtlJPC7nqtxWdSO07SRoUVth',
        'applicationSid' => 'APe6600c68fbdf35840310c0954ba80e08',
        'number' => '+15672352709',
    ],*/

];
