<?php

namespace App\Exports\Member;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MemberImportSampleExport implements FromArray, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    public function array(): array
    {
        // Sample data
        return [
            [
                'MBR-2024-001',
                'John',
                'Doe',
                'Male',
                '1199999999999999',
                '1990-01-15',
                '+250',
                '788123456',
                'john.doe@example.com',
                'Kigali, Rwanda',
                'active'
            ],
            [
                'MBR-2024-002',
                'Jane',
                'Smith',
                'Female',
                '1198888888888888',
                '1992-05-20',
                '+250',
                '789987654',
                'jane.smith@example.com',
                'Nyarugenge, Kigali',
                'active'
            ],
            [
                'MBR-2024-003',
                'Robert',
                'Johnson',
                'Male',
                '1197777777777777',
                '1985-11-30',
                '+250',
                '783456789',
                'robert.j@example.com',
                'Gisenyi, Rubavu',
                'inactive'
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Reference (Auto-generated if empty)',
            'First Name*',
            'Last Name*',
            'Gender* (Male/Female)',
            'National ID Number (Optional)',
            'Date of Birth (YYYY-MM-DD)',
            'Phone Code (e.g., +250)',
            'Phone Number',
            'Email (Optional)',
            'Address (Optional)',
            'Status (active/inactive, Default: active)'
        ];
    }

    public function title(): string
    {
        return 'Member Import Template';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Reference
            'B' => 20, // First Name
            'C' => 20, // Last Name
            'D' => 15, // Gender
            'E' => 25, // National ID
            'F' => 20, // Date of Birth
            'G' => 15, // Phone Code
            'H' => 20, // Phone Number
            'I' => 30, // Email
            'J' => 30, // Address
            'K' => 15, // Status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Make header row bold
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        // Add background color to header
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Add borders
        $sheet->getStyle('A1:K4')->getBorders()
            ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        return [
            // Style the first row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
