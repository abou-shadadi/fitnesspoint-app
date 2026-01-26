<?php

namespace App\Exports\Member;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FailedMembersExport implements FromCollection, WithHeadings
{
    protected $failedRows;
    protected $type; // 'corporate' or 'individual'

    public function __construct($failedRows, $type = 'corporate')
    {
        $this->failedRows = $failedRows;
        $this->type = $type;
    }

    public function collection()
    {
        return collect($this->failedRows);
    }

    public function headings(): array
    {
        $headings = [
            'Reference',
            'Name',
            'Gender',
            'National ID Number',
            'Date of Birth',
            'Phone',
            'Email',
            'Address',
            'Error Message',
        ];

        // Add membership start date for individual members
        if ($this->type === 'individual') {
            // Insert membership start date before error message
            array_splice($headings, 8, 0, 'Membership Start Date');
        }

        return $headings;
    }
}
