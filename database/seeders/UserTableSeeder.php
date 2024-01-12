<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
    
        DB::table('users')->delete();
        
        DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'Admin',
                'contact_number' => '+923165328440',
                'email' => 'admin@admin.com',
                'password' => bcrypt('12345678'),
                'email_verified_at' => NULL,
                'user_type' => 'admin',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => NULL,
            )
        ));       
        
    }
}