<?php

namespace App\Exports\Member;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FailedMembersExport implements FromCollection, WithHeadings
{
    protected $failedRows;

    public function __construct($failedRows)
    {
        $this->failedRows = $failedRows;
    }

    public function collection()
    {
        return collect($this->failedRows);
    }

    public function headings(): array
    {
        return [
            'Reference',
            'First Name',
            'Last Name',
            'Gender',
            'National ID Number',
            'Date of Birth',
            'Phone Code',
            'Phone Number',
            'Email',
            'Address',
            'Status',
        ];
    }
}
