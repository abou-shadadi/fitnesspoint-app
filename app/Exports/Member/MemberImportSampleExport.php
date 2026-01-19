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
    protected $type; // 'corporate' or 'individual'

    public function __construct($type = 'corporate')
    {
        $this->type = $type;
    }

    public function array(): array
    {
        if ($this->type === 'individual') {
            // Sample data for individual members (with membership start date)
            return [
                [
                    'MBR-2024-001',
                    'John',
                    'Doe',
                    'Male',
                    '1199999999999999',
                    '1990-01-15',
                    '0788123456',
                    'john.doe@example.com',
                    'Kigali, Rwanda',
                    '2024-01-01'
                ],
                [
                    'MBR-2024-002',
                    'Jane',
                    'Smith',
                    'Female',
                    '1198888888888888',
                    '1992-05-20',
                    '250789987654',
                    'jane.smith@example.com',
                    'Nyarugenge, Kigali',
                    '2024-01-15'
                ],
                [
                    'MBR-2024-003',
                    'Robert',
                    'Johnson',
                    'Male',
                    '1197777777777777',
                    '1985',
                    '798765432',
                    'robert.j@example.com',
                    'Gisenyi, Rubavu',
                    '2024-02-01'
                ],
            ];
        } else {
            // Sample data for corporate members (without membership start date)
            return [
                [
                    'MBR-2024-001',
                    'John',
                    'Doe',
                    'Male',
                    '1199999999999999',
                    '1990-01-15',
                    '0788123456',
                    'john.doe@example.com',
                    'Kigali, Rwanda',
                ],
                [
                    'MBR-2024-002',
                    'Jane',
                    'Smith',
                    'Female',
                    '1198888888888888',
                    '1992-05-20',
                    '250789987654',
                    'jane.smith@example.com',
                    'Nyarugenge, Kigali',
                ],
                [
                    'MBR-2024-003',
                    'Robert',
                    'Johnson',
                    'Male',
                    '1197777777777777',
                    '1985',
                    '798765432',
                    'robert.j@example.com',
                    'Gisenyi, Rubavu',
                ],
            ];
        }
    }

    public function headings(): array
    {
        $headings = [
            'Reference',
            'First Name',
            'Last Name',
            'Gender',
            'National ID Number',
            'Date of Birth',
            'Phone',
            'Email',
            'Address',
        ];

        // Add membership start date for individual members
        if ($this->type === 'individual') {
            $headings[] = 'Membership Start Date (Optional: YYYY-MM-DD)';
        }

        return $headings;
    }

    public function title(): string
    {
        return $this->type === 'individual' ? 'Individual Members Template' : 'Corporate Members Template';
    }

    public function columnWidths(): array
    {
        if ($this->type === 'individual') {
            return [
                'A' => 30, // Reference
                'B' => 20, // First Name
                'C' => 20, // Last Name
                'D' => 15, // Gender
                'E' => 25, // National ID
                'F' => 25, // Date of Birth
                'G' => 25, // Phone
                'H' => 30, // Email
                'I' => 30, // Address
                'J' => 25, // Membership Start Date
            ];
        } else {
            return [
                'A' => 30, // Reference
                'B' => 20, // First Name
                'C' => 20, // Last Name
                'D' => 15, // Gender
                'E' => 25, // National ID
                'F' => 25, // Date of Birth
                'G' => 25, // Phone
                'H' => 30, // Email
                'I' => 30, // Address
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        // Get the number of columns based on type
        $lastColumn = $this->type === 'individual' ? 'J' : 'I';

        // Make header row bold
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);

        // Add background color to header
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Add borders
        $sheet->getStyle("A1:{$lastColumn}4")->getBorders()
            ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        return [
            // Style the first row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
