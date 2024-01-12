<?php

namespace App\Exports;

use App\Models\Contactlist;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ContactlistExport implements FromCollection, WithHeadings
{
    private $list_id = null;
    public function  __construct($list_id)
    {
        $this->list_id = $list_id;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Contactlist::select('name', 'contact', 'email', 'zip_code', 'Notes', 'address')->where('list_id', $this->list_id)->get();
    }

    public function headings(): array
    {
        return ["Name", "Contact", "Email", "Zip Code", "Notes", "Address"];
    }
}
