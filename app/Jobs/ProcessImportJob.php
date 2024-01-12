<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use App\Models\Contactlist;
use App\Models\Lists;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $header;
    public $data;
    public $file;
    public $list_id;
    public $user_id;

    public function __construct($file, $list_id, $user_id)
    {
        $this->list_id = $list_id;
        $this->user_id = $user_id;
        $this->file = $file;
    }

    public function handle()
    {
        Log::info("user id " . $this->user_id);
        $csv = file($this->file);
        $chunks = array_chunk($csv, 1000);
        $header = [];

        foreach ($chunks as $key => $chunk) {
            $data = array_map('str_getcsv', $chunk);

            if ($key === 0) {
                array_push($header, 'name', 'contact', 'email', 'zip_code', 'notes', 'address', 'list_id', 'state', 'user_id');
                unset($data[0]);
            }

            foreach ($data as $d) {
                if(isset($d[1]) && $d[1] !=''){
                $d[6] = $this->list_id ?? null;
                $d[7] = '';
                $d[8] =  $this->user_id; // Assign the user_id value
                $d[1] = $this->formatNumber($d[1]);
                $d[0] = $this->cleanStr($d[0]??'');
                $d[2] = $this->cleanStr($d[2]??'');
                $d[3] = $this->cleanStr($d[3]??'');
                $d[4] = $this->cleanStr($d[4]??'');
                $d[5] = $this->cleanStr($d[5]??'');
                $item_csv_data = array_combine($header, $d);
                Contactlist::create($item_csv_data);
                }
            }
        }
    }
    public function cleanStr($str = null){
        $str = trim(preg_replace('/[\t\n\r\s]+/', ' ', $str));
        $str = preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', $str);
        
        return $str;
    }
    public function formatNumber($number = null)
    {
        $number = trim($number);
        $number = str_replace('-', '', $number);
        $number = str_replace(' ', '', $number);
        $number = str_replace('(', '', $number);
        $number = str_replace(')', '', $number);
        $number = str_replace('.', '', $number);
        $number = trim(preg_replace('/[\t\n\r\s]+/', '', $number));
        $number = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $number);

        $twoDigit = substr($number, 2);
        if ($twoDigit != '+1' && strlen($number) == 10) {
            $number = '+1' . $number;
        }
        return $number;
    }
}
