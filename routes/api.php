
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Http\Controllers\API;
use App\Http\Controllers\vendor\Chatify\Api\MessagesController;
use App\Http\Controllers\TwilioSMSController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return response()->json('APIs are running!');
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [API\UserController::class, 'register']);
Route::post('login', [API\UserController::class, 'login']);
Route::post('forget-password', [API\UserController::class, 'forgetPassword']);

Route::post('sendMessages', [ChatsController::class, 'sendMessage']);

Route::post('forgetPasswordOtp', [API\UserController::class, 'forgetPasswordOtp']);
Route::post('getforgetPasswordOtp', [API\UserController::class, 'getforgetPasswordOtp']);

Route::post('emailCheck', [API\UserController::class, 'emailCheck']);
Route::get('user-list', [API\UserController::class, 'userList']);
Route::post('user-detail', [API\UserController::class, 'userDetail']);

Route::post('estimate-price-time', [API\ServiceController::class, 'estimatePriceTime']);
Route::group(['middleware' => ['auth:sanctum']], function () {
    /*****Admin APIs*****/
    Route::put('admin/create-category', [API\AdminController::class, 'create_category']);
    Route::get('admin/categories/{user_id?}', [API\AdminController::class, 'getCategories']);
    Route::get('admin/getAppSettings', [API\AdminController::class, 'getAppSetting']);
    Route::put('admin/update-app-setting', [API\AdminController::class, 'update_app_settings']);
    Route::get('admin/category/{id}', [API\AdminController::class, 'getCategoryById']);
    Route::put('admin/update-category', [API\AdminController::class, 'updateCategory']);
    Route::delete('admin/destroy-category/{id}', [API\AdminController::class, 'destroyCategory']);

    Route::get('admin/contacts/{user_id?}/{keywords?}/{list_id?}', [API\AdminController::class, 'getContacts']);
    Route::get('admin/getAllContacts/{user_id?}', [API\AdminController::class, 'getAllContacts']);
    Route::get('admin/category-contacts/{category_id?}', [API\AdminController::class, 'getContactsByCategory']);
    Route::get('admin/list-contacts/{list_id?}/{keywords?}', [API\AdminController::class, 'getContactsByList']);
    Route::get('admin/list-all-contacts/{list_id?}', [API\AdminController::class, 'getAllContactsByList']);
    Route::get('admin/contact/{id}', [API\AdminController::class, 'getContactsById']);
    Route::get('admin/getContactsPreviousMonth', [API\AdminController::class, 'getContactsPreviousMonth']);

    Route::put('admin/contacts/create', [API\AdminController::class, 'create_contact']);
    Route::put('admin/update-contact', [API\AdminController::class, 'updateContactlist']);
    Route::put('admin/updateContactlistStatus', [API\AdminController::class, 'updateContactlistStatus']);

    Route::delete('admin/destroy-contact/{id}', [API\AdminController::class, 'destroyContact']);

    Route::get('admin/users/{id?}', [API\AdminController::class, 'getUsers']);
    Route::get('admin/getProfile', [API\AdminController::class, 'getProfile']);

    Route::put('admin/change-user-password', [API\AdminController::class, 'changeUserPassword']);
    Route::put('admin/update-user', [API\AdminController::class, 'updateUser']);
    // updateUserStatus/updateUserStatus
    Route::put('admin/updateUserStatus', [API\AdminController::class, 'updateUserStatus']);

    Route::put('admin/change-user-daily-text-limit', [API\AdminController::class, 'changeDailyTextLimit']);
    Route::delete('admin/delete-user/{id}', [API\AdminController::class, 'destroyUser']);

    Route::put('admin/add-banned-word', [API\AdminController::class, 'storeBannedWord']);
    Route::put('admin/update-banned-word', [API\AdminController::class, 'updateBannedWord']);
    Route::get('admin/banned-words/{id?}', [API\AdminController::class, 'getBannedWords']);
    Route::delete('admin/delete-banned-words/{id}', [API\AdminController::class, 'destroyBanWords']);

    Route::get('admin/lists/{id?}/{keywords?}', [API\AdminController::class, 'lists']);
    Route::get('admin/all-lists/{id?}', [API\AdminController::class, 'allLists']);
    Route::get('admin/listsById/{id}', [API\AdminController::class, 'listsById']);
    Route::put('admin/lists/create', [API\AdminController::class, 'create_list']);
    // Route::get('admin/lists/show/{id}',[ API\ListsController::class, 'show']);
    Route::put('admin/update-list', [API\AdminController::class, 'updateList']);
    Route::delete('admin/destroy-list/{id}', [API\AdminController::class, 'destroyList']);

    // campaigns
    Route::get('admin/campaigns/{user_id?}', [API\AdminController::class, 'campaigns']);
    Route::get('admin/campaignById/{id}', [API\AdminController::class, 'campaignById']);
    Route::put('admin/campaigns/create', [API\AdminController::class, 'create_campaigns']);
    Route::put('admin/campaigns/update', [API\AdminController::class, 'update_campaign']);
    // dashboard


    Route::get('totalSMSs/{id?}', [API\ChatsController::class, 'totalSMSs']);
    Route::get('deliveredSMSs/{id?}', [API\ChatsController::class, 'deliveredSMSs']);
    Route::get('delivereRatePerCampaign/{id?}', [API\ChatsController::class, 'delivereRatePerCampaign']);
    Route::get('spamRatePerCampaign/{id?}', [API\ChatsController::class, 'spamRatePerCampaign']);

    Route::get('deliveryRates/{id?}', [API\ChatsController::class, 'deliveryRates']);
    Route::get('campaignRates/{id?}', [API\CampaignsController::class, 'campaignRates']);
    Route::get('spamRates/{id?}', [API\ChatsController::class, 'spamRates']);
    Route::get('unsubscribeRates', [API\ContactlistController::class, 'unsubscribeRates']);
    Route::post('getState', [API\ContactlistController::class, 'getState']);
    Route::get('validateMobile', [API\ContactlistController::class, 'validateMobile']);
    Route::get('getStatisticsMonthWise', [API\CampaignsController::class, 'getStatisticsMonthWise']);
    Route::get('getSmsStatisticsMonthWise', [API\ChatsController::class, 'getStatisticsMonthWise']);

    /*****User APIs Start*****/
    //Notifications 

    Route::get('notifications', [API\NotificationsController::class, 'index']);
    Route::get('notifications/update', [API\NotificationsController::class, 'update']);
    // NotificationsController
    //General Chat

    // Route::get('chat', [API\ChatsController::class, 'index']);
    Route::get('chat', [ApI\ChatsController::class, 'fetchMessages']);
    Route::post('chat/create', [ApI\ChatsController::class, 'sendMessage']);
    Route::get('chat/userDailyTxtLimit', [ApI\ChatsController::class, 'userDailyTxtLimit']);

    Route::post('chat/create-twilio', [ApI\ChatsController::class, 'sendMessageToSubscriber']);

    // Route::get('chat/getAllChatsByUser/{user_id}/{order_by?}/{keywords?}', [ApI\ChatsController::class, 'getAllChatsByUser']);
    Route::post('chat/getAllChatsByUser', [ApI\ChatsController::class, 'getAllChatsByUserWithSort']);
    Route::get('chat/readStatusUpdate/{subscriber_id}', [ApI\ChatsController::class, 'readStatusUpdate']);
    Route::get('chat/getNotifications', [ApI\ChatsController::class, 'getNotifications']);

    Route::get('chat/getUserChat/{id}', [ApI\ChatsController::class, 'getUserChat']);
    Route::get('chat/deleteChat/{subscriber_id}', [ApI\ChatsController::class, 'deleteChat']);

    Route::post('chat/twilioVoiceCall', [ApI\ChatsController::class, 'twilioVoiceCall']);
    Route::get('twilioVoiceCallToken', [ApI\TokenController::class, 'newToken']);
    Route::get('call-now', [ApI\CallController::class, 'newCall']);

    // Route::post('chat',[ API\ChatController::class, 'index']);
    // Route::post('chat/add',[ API\ChatController::class, 'create']);
    //Users
    Route::post('change-password', [API\UserController::class, 'changePassword']);
    Route::put('updateProfile', [API\UserController::class, 'updateProfile']);
    
    Route::get('getProfile', [API\UserController::class, 'getProfile']);
    //Categories
    Route::get('categories', [API\CategoriesController::class, 'index']);
    Route::put('categories/store', [API\CategoriesController::class, 'store']);
    Route::get('categories/show/{id}', [API\CategoriesController::class, 'show']);
    Route::put('categories/update', [API\CategoriesController::class, 'update']);
    Route::delete('categories/destroy/{id}', [API\CategoriesController::class, 'destroy']);
    //Categories
    Route::get('lists/{keywords?}', [API\ListsController::class, 'index']);
    Route::get('list/getAllLists', [API\ListsController::class, 'getAllLists']);

    Route::put('lists/store', [API\ListsController::class, 'store']);
    Route::get('lists/show/{id}', [API\ListsController::class, 'show']);
    Route::put('lists/update', [API\ListsController::class, 'update']);
    Route::delete('lists/destroy/{id}', [API\ListsController::class, 'destroy']);
    //Campaign APIs
    Route::get('campaigns', [API\CampaignsController::class, 'index']);
    Route::get('campaigns/lists/{id}', [API\CampaignsController::class, 'getCampaignLists']);
    Route::get('campaigns/send-to/{id}', [API\CampaignsController::class, 'getCampaignSendTo']);
    Route::get('campaigns/not-send-to/{id}', [API\CampaignsController::class, 'getCampaignNotSendTo']);

    Route::put('campaigns/store', [API\CampaignsController::class, 'store']);
    Route::get('campaigns/show/{id}', [API\CampaignsController::class, 'show']);
    Route::put('campaigns/update', [API\CampaignsController::class, 'update']);
    Route::put('campaigns/update-campaign-list', [API\CampaignsController::class, 'updateLists']);
    Route::put('campaigns/update-campaign-send-to', [API\CampaignsController::class, 'updateCampaignSendTo']);
    Route::put('campaigns/update-campaign-not-send-to', [API\CampaignsController::class, 'updateCampaignNotSendTo']);

    Route::delete('campaigns/destroy/{id}', [API\CampaignsController::class, 'destroy']);
    //Contacts

    // Route::post('contacts/importCSV', [API\ContactlistController::class, 'import'])->name('import');
    Route::post('contacts/importCSV', [API\ContactlistController::class, 'upload_csv_file'])->name('upload_csv_file');
    Route::post('contacts/exportCSV', [API\ContactlistController::class, 'export'])->name('export');
    // Route::get('export', [MyController::class, 'export'])->name('export');
    Route::get('contacts', [API\ContactlistController::class, 'index']);

    Route::get('contacts/getAllContacts', [API\ContactlistController::class, 'getAllContacts']);
    // getAllContacts
    Route::get('contacts/getContactsPreviousMonth', [API\ContactlistController::class, 'getContactsPreviousMonth']);
    Route::get('contacts/{keywords?}/{list_id?}', [API\ContactlistController::class, 'index']);
    Route::put('contacts/store', [API\ContactlistController::class, 'store']);
    Route::put('contacts/storeNewContact', [API\ContactlistController::class, 'storeNewContact']);
    Route::put('contacts/createNewChat', [API\ContactlistController::class, 'createNewChat']);

    Route::get('contacts/show/{id}', [API\ContactlistController::class, 'show']);
    Route::put('contacts/update', [API\ContactlistController::class, 'update']);

    Route::put('contacts/updateContactlistStatus', [API\ContactlistController::class, 'updateContactlistStatus']);
    Route::delete('contacts/destroy/{id}', [API\ContactlistController::class, 'destroy']);
    
    Route::get('contact/listing/{id}/{keywords?}', [API\ContactlistController::class, 'getByList']);
    Route::get('contact/list-all/{id}', [API\ContactlistController::class, 'getAllByList']);
    Route::post('contacts/search-by-keywords', [API\ContactlistController::class, 'searchContactList']);


    Route::get('logout', [API\UserController::class, 'logout']);
    Route::get('isonline', [API\UserController::class, 'isonline']);
});

Route::get('test', [API\UserController::class, 'testfunction']);
