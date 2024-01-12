<?php

namespace App\Imports;

use App\Models\Contactlist;
use App\Models\Lists;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Str;

class ImportContactlist implements ToModel, WithStartRow
{
    private $list_id = null;
    public function  __construct($list_id)
    {
        $this->list_id= $list_id;
    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // return $this->list_id;
            if($this->list_id && $this->list_id!=''){
                $list = Lists::where(['is_deleted'=>'0', 'id'=>$this->list_id])->first();
                $valid = validate_mobile($row[1]);
                // $valid = true;
                if($list && $list->user_id && $valid){
                    $data = [
                        'name'      => $row[0],
                        'contact'   => $valid,
                        'email'     => $row[2],
                        'zip_code'  => $row[3],
                        'state'  => getCityState($row[3])??null,
                        'notes'     => $row[4],
                        'address'   => $row[5],
                        'status'    => 'active',
                        'user_id'   => $list->user_id,
                        'list_id'   => $this->list_id,
                    ];
                    return new Contactlist($data);    
                }
            }
    }


    public function startRow(): int
    {
        return 2;
    }
    
}

            