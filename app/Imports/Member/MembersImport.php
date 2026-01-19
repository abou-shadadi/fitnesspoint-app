<?php

namespace App\Imports\Member;

use App\Models\Member\Member;
use App\Models\Member\MemberImportLog;
use App\Models\Company\Company;
use App\Models\Branch\Branch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MembersImport implements ToCollection, WithHeadingRow
{
    protected $predefinedColumns = [
        'reference',
        'first_name',
        'last_name',
        'gender',
        'national_id_number',
        'date_of_birth',
        'phone',
        'email',
        'address',
        'membership_start_date', // For individual members
    ];

    public $branch, $user, $importId;
    public $memberSubscriptionId, $companySubscriptionId;
    protected $failedRows = [];
    protected $successCount = 0;
    protected $failedCount = 0;

    public function __construct($branchId, $userId, $importId = null, $memberSubscriptionId = null, $companySubscriptionId = null)
    {
        $this->branch = Branch::find($branchId);
        if (!$this->branch) {
            throw new \Exception('Branch not found');
        }

        $this->user = User::find($userId);
        if (!$this->user) {
            throw new \Exception('User not found');
        }

        $this->importId = $importId;
        $this->memberSubscriptionId = $memberSubscriptionId;
        $this->companySubscriptionId = $companySubscriptionId;
    }

    public function collection(Collection $rows)
    {
        Log::info('Starting Member Import Collection...');

        // Reset counters
        $this->successCount = 0;
        $this->failedCount = 0;

        // Process each row independently
        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber === 0) {
                // Skip header row
                continue;
            }

            // Process each row independently
            $this->processRow($row, $rowNumber);
        }

        Log::info("Member import completed. Success: {$this->successCount}, Failed: {$this->failedCount}");
    }

    private function processRow($row, $rowNumber)
    {
        $importComment = '';
        $excelRowNumber = $rowNumber + 1; // Excel rows are 1-indexed
        Log::info("Processing row {$excelRowNumber}");

        try {
            // Skip empty rows (where all required fields are empty)
            if ($this->isRowEmpty($row)) {
                Log::info("Skipping empty row {$excelRowNumber}");
                return;
            }

            // Validate only this row
            $validationResult = $this->validateSingleRow($row, $excelRowNumber);

            if (!$validationResult['valid']) {
                throw new \Exception($validationResult['errors']);
            }

            // Process gender
            $gender = $this->processGender($row);

            // Generate reference if empty
            if (empty($row['reference'])) {
                $row['reference'] = $this->generateMemberReference();
            }

            // Check if member exists
            $this->checkMemberExistence($row);

            // Parse phone
            $phone = $this->parsePhone($row['phone'] ?? null);

            // Create member in a transaction for this row only
            DB::beginTransaction();
            try {
                $member = $this->createMember($row, $gender, $phone);
                DB::commit();

                Log::info("Successfully created member: {$member->reference}");
                $this->successCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                throw new \Exception($e->getMessage());
            }

        } catch (\Exception $e) {
            // Log error and continue processing other rows
            $this->handleRowError($row, $excelRowNumber, $e->getMessage(), $importComment);
            $this->failedCount++;

            // Continue to next row - don't throw exception
            return;
        }
    }

    private function validateSingleRow($row, $rowNumber)
    {
        // Convert row to array for validation
        $rowData = $row->toArray();

        // Define validation rules for this specific row
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female,Male,Female,M,F,other,Other',
            'date_of_birth' => 'nullable',
            'email' => 'nullable|email|unique:members,email',
            'national_id_number' => 'nullable',
            'reference' => 'nullable|string|unique:members,reference',
            'phone' => 'nullable',
            'membership_start_date' => 'nullable|date',
        ];

        // Custom error messages
        $customMessages = [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'gender.in' => 'Gender must be Male, Female, or Other',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email already exists',
            'reference.unique' => 'Reference number already exists',
            'membership_start_date.date' => 'Membership start date must be a valid date',
        ];

        // Validate the row
        $validator = Validator::make($rowData, $rules, $customMessages);

        if ($validator->fails()) {
            // Collect all error messages for this row
            $errorMessages = [];
            foreach ($validator->errors()->all() as $error) {
                $errorMessages[] = $error;
            }

            return [
                'valid' => false,
                'errors' => "Row {$rowNumber}: " . implode(' | ', $errorMessages)
            ];
        }

        return ['valid' => true, 'errors' => null];
    }

    private function isRowEmpty($row)
    {
        $requiredFields = ['first_name', 'last_name'];

        foreach ($requiredFields as $field) {
            if (!empty($row[$field])) {
                return false;
            }
        }

        return true;
    }

    private function handleRowError($row, $rowNumber, $errorMessage, $importComment)
    {
        $rowData = $row->toArray();
        $rowData['row'] = $rowNumber;
        $rowData['comment'] = $importComment ?: $errorMessage;

        Log::error("Error in row {$rowNumber}: {$errorMessage}");
        $this->logFailedRow($rowData, $rowNumber, $errorMessage);
    }

    private function logFailedRow($rowData, $rowNumber, $errorMessage)
    {
        if ($this->importId) {
            try {
                MemberImportLog::create([
                    'log_message' => "Error in row {$rowNumber}: {$errorMessage}",
                    'member_import_id' => $this->importId,
                    'is_resolved' => false,
                    'data' => $rowData,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create import log: " . $e->getMessage());
            }
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    private function processGender($row)
    {
        if (!isset($row['gender']) || empty($row['gender'])) {
            return 'other';
        }

        $gender = strtolower(trim($row['gender']));

        if (in_array($gender, ['male', 'm'])) {
            return 'male';
        } elseif (in_array($gender, ['female', 'f'])) {
            return 'female';
        } else {
            return 'other';
        }
    }

    private function checkMemberExistence($row)
    {
        // Check by reference
        if (isset($row['reference']) && Member::where('reference', $row['reference'])->exists()) {
            throw new \Exception('Member with this reference already exists');
        }

        // Check by email
        if (isset($row['email']) && Member::where('email', $row['email'])->exists()) {
            throw new \Exception('Member with this email already exists');
        }

        // Check by national ID
        if (isset($row['national_id_number']) && Member::where('national_id_number', $row['national_id_number'])->exists()) {
            throw new \Exception('Member with this national ID number already exists');
        }
    }

    private function parsePhone($phone)
    {
        if (empty($phone)) {
            return null;
        }

        $phone = trim((string) $phone);

        // Remove all non-digit characters except plus
        $cleaned = preg_replace('/[^\d\+]/', '', $phone);

        // Default country code for Rwanda
        $countryCode = '250';

        // Handle different phone formats
        if (Str::startsWith($cleaned, '250')) {
            // Format: 2507XXXXXXXXX
            $number = substr($cleaned, 3);
            $code = '250';
        } elseif (Str::startsWith($cleaned, '07')) {
            // Format: 07XXXXXXXXX
            $number = substr($cleaned, 1);
            $code = '250';
        } elseif (Str::startsWith($cleaned, '7')) {
            // Format: 7XXXXXXXXX
            $number = $cleaned;
            $code = '250';
        } elseif (Str::startsWith($cleaned, '+')) {
            // Format: +2507XXXXXXXXX
            $cleaned = ltrim($cleaned, '+');
            if (Str::startsWith($cleaned, '250')) {
                $number = substr($cleaned, 3);
                $code = '250';
            } else {
                // Other country code
                preg_match('/^(\d{1,3})(\d+)/', $cleaned, $matches);
                if (count($matches) === 3) {
                    $code = $matches[1];
                    $number = $matches[2];
                } else {
                    $code = null;
                    $number = $cleaned;
                }
            }
        } else {
            // Unknown format, use default
            $number = $cleaned;
            $code = '250';
        }

        // Ensure number starts with 7 for Rwanda
        if ($code === '250' && !Str::startsWith($number, '7')) {
            if (Str::startsWith($number, '0')) {
                $number = substr($number, 1);
            }
            if (!Str::startsWith($number, '7')) {
                $number = '7' . $number;
            }
        }

        return [
            'code' => '+' . $code,
            'number' => $number
        ];
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Handle numeric Excel dates
            if (is_numeric($date)) {
                try {
                    $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    // Not an Excel date, try other formats
                }
            }

            // Convert to string
            $dateStr = (string) $date;

            // Try to parse as year only (e.g., 1990)
            if (preg_match('/^\d{4}$/', $dateStr)) {
                return $dateStr . '-01-01'; // Use January 1st of that year
            }

            // Try to parse as Carbon date
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$date}. Error: " . $e->getMessage());
            return null;
        }
    }

    private function createMember($row, $gender, $phone)
    {
        // Prepare member data
        $memberData = [
            'reference' => $row['reference'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'gender' => $gender,
            'date_of_birth' => $this->parseDate($row['date_of_birth'] ?? null),
            'email' => $row['email'] ?? null,
            'national_id_number' => $this->parseNationalId($row['national_id_number'] ?? null),
            'phone' => $phone,
            'address' => $row['address'] ?? null,
            'created_by_id' => $this->user->id,
            // 'branch_id' => $this->branch->id,
        ];

        // Add subscription references if provided
        if ($this->memberSubscriptionId) {
            $memberData['member_subscription_id'] = $this->memberSubscriptionId;
        }

        if ($this->companySubscriptionId) {
            $memberData['company_subscription_id'] = $this->companySubscriptionId;
        }

        // Parse membership start date for individual members
        if (isset($row['membership_start_date'])) {
            $startDate = $this->parseDate($row['membership_start_date']);
            if ($startDate) {
                $memberData['membership_start_date'] = $startDate;
            }
        }

        // Create the member
        return Member::create($memberData);
    }

    private function parseNationalId($nationalId)
    {
        if (empty($nationalId)) {
            return null;
        }

        // Convert to string and trim
        $nationalId = (string) $nationalId;
        $nationalId = trim($nationalId);

        return $nationalId;
    }

    private function generateMemberReference()
    {
        do {
            $reference = 'MBR-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Member::where('reference', $reference)->exists());

        return $reference;
    }

    public function getFailedRows()
    {
        return $this->failedRows;
    }

    public function getImportStatistics()
    {
        return [
            'success_count' => $this->successCount,
            'failed_count' => $this->failedCount,
            'total_processed' => $this->successCount + $this->failedCount,
        ];
    }
}
