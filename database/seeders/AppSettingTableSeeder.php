<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSettingTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        DB::table('app_settings')->delete();
        
        DB::table('app_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'site_name' => 'Mighty Taxi',
                'site_email' => '',
                'site_logo' => '',
                'site_favicon' => '',
                'site_description' => '',
                'site_copyright' => '',
                'facebook_url' => 'fb.com',
                'instagram_url' => 'instagram.com',
                'twitter_url' => 'twitter.com',
                'linkedin_url' => 'linkedin.com',
                'language_option' => '["en"]',
                'contact_email' => 'admin@admin.com',
                'contact_number' => '+923001234567',
                'help_support_url' => '',
            ),
        ));
        
        
    }
}