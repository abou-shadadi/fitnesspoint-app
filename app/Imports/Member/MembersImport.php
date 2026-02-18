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
        'name',
        'gender',
        'national_id_number',
        'date_of_birth',
        'phone',
        'email',
        'address',
        'membership_start_date',
    ];

    public $branch, $user, $importId;
    public $planId, $companySubscriptionId;
    protected $failedRows = [];
    protected $successCount = 0;
    protected $failedCount = 0;
    protected $type;
    protected $importRecord;

    public function __construct($branchId, $userId, $importId = null, $planId = null, $companySubscriptionId = null)
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
        $this->planId = $planId;
        $this->companySubscriptionId = $companySubscriptionId;

        // Get import record and type
        if ($importId) {
            $this->importRecord = \App\Models\Member\MemberImport::find($importId);
            $this->type = $this->importRecord->type ?? 'corporate';
        } else {
            $this->type = 'corporate';
        }
    }

    public function collection(Collection $rows)
    {
        Log::info('Starting Member Import Collection...', [
            'import_id' => $this->importId,
            'type' => $this->type,
            'total_rows' => $rows->count()
        ]);

        // Reset counters
        $this->successCount = 0;
        $this->failedCount = 0;
        $this->failedRows = [];

        // Process each row
        foreach ($rows as $rowNumber => $row) {
            $excelRowNumber = $rowNumber + 2; // +2 because: +1 for 1-indexed, +1 for header row

            try {
                // Process the row
                $this->processRow($row, $excelRowNumber);
            } catch (\Exception $e) {
                // This should not happen as processRow catches its own exceptions
                Log::error("Unexpected error processing row {$excelRowNumber}: " . $e->getMessage());
                continue;
            }
        }

        Log::info("Member import completed.", [
            'import_id' => $this->importId,
            'success_count' => $this->successCount,
            'failed_count' => $this->failedCount
        ]);
    }

    private function processRow($row, $rowNumber)
    {
        Log::debug("Processing row {$rowNumber}");

        try {
            // Skip empty rows
            if ($this->isRowEmpty($row)) {
                Log::debug("Skipping empty row {$rowNumber}");
                return;
            }

            // Validate the row
            $validationResult = $this->validateSingleRow($row, $rowNumber);
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

            // Parse name into first_name and last_name
            $nameParts = $this->parseName($row['name'] ?? '');

            // Create member in a transaction
            DB::beginTransaction();
            try {
                $member = $this->createMember($row, $nameParts, $gender, $phone);
                DB::commit();

                Log::info("Successfully created member: {$member->reference}");
                $this->successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                throw new \Exception($e->getMessage());
            }
        } catch (\Exception $e) {
            // Log the error to MemberImportLog
            $this->logFailedRowToDatabase($row, $rowNumber, $e->getMessage());

            // Add to failed rows collection for export
            $this->addFailedRow($row, $rowNumber, $e->getMessage());

            // Increment failed count
            $this->failedCount++;

            Log::warning("Failed to import row {$rowNumber}: " . $e->getMessage());

            // Continue processing other rows - DO NOT re-throw
        }
    }

    private function validateSingleRow($row, $rowNumber)
    {
        // Convert row to array for validation
        $rowData = $row->toArray();

        // Define validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female,Male,Female,M,F,other,Other',
            'date_of_birth' => 'nullable',
            'email' => 'nullable|email|unique:members,email',
            'national_id_number' => 'nullable',
            'reference' => 'nullable|string|unique:members,reference',
            'phone' => 'nullable',
        ];

        // Add membership start date rule for individual imports
        if ($this->type === 'individual') {
            $rules['membership_start_date'] = 'nullable|date';
        }

        // Custom error messages
        $customMessages = [
            'name.required' => 'Name is required',
            'gender.in' => 'Gender must be Male, Female, or Other',
            'email.email' => 'Email must be a valid email address',
            'email.unique' => 'Email already exists',
            'reference.unique' => 'Reference number already exists',
            'membership_start_date.date' => 'Membership start date must be a valid date',
        ];

        // Validate the row
        $validator = Validator::make($rowData, $rules, $customMessages);

        if ($validator->fails()) {
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
        $requiredFields = ['name'];

        foreach ($requiredFields as $field) {
            if (!empty($row[$field])) {
                return false;
            }
        }

        return true;
    }

    private function parseName($name)
    {
        if (empty($name)) {
            throw new \Exception('Name is required');
        }

        $name = trim($name);

        // Split name by spaces
        $parts = preg_split('/\s+/', $name, 2);

        if (count($parts) === 1) {
            return [
                'first_name' => $parts[0],
                'last_name' => ''
            ];
        } else {
            return [
                'first_name' => $parts[0],
                'last_name' => $parts[1]
            ];
        }
    }

    private function logFailedRowToDatabase($row, $rowNumber, $errorMessage)
    {
        if ($this->importId) {
            try {
                MemberImportLog::create([
                    'member_import_id' => $this->importId,
                    'log_message' => $errorMessage,
                    'is_resolved' => false,
                    'data' => [
                        'row_number' => $rowNumber,
                        'row_data' => $row->toArray(),
                        'error' => $errorMessage,
                        'failed_at' => now()->toDateTimeString()
                    ],
                ]);

                Log::debug("Logged failed row {$rowNumber} to MemberImportLog");
            } catch (\Exception $e) {
                Log::error("Failed to create import log for row {$rowNumber}: " . $e->getMessage());
            }
        }
    }

    private function addFailedRow($row, $rowNumber, $errorMessage)
    {
        $rowData = $row->toArray();

        // Prepare failed row data for export
        $failedRow = [
            'Reference' => $rowData['reference'] ?? '',
            'Name' => $rowData['name'] ?? '',
            'Gender' => $rowData['gender'] ?? '',
            'National ID Number' => $rowData['national_id_number'] ?? '',
            'Date of Birth' => $rowData['date_of_birth'] ?? '',
            'Phone' => $rowData['phone'] ?? '',
            'Email' => $rowData['email'] ?? '',
            'Address' => $rowData['address'] ?? '',
            'Error Message' => $errorMessage,
        ];

        // Add membership start date for individual imports
        if ($this->type === 'individual') {
            $failedRow['Membership Start Date'] = $rowData['membership_start_date'] ?? '';
        }

        $this->failedRows[] = $failedRow;
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
        if (
            isset($row['reference']) && !empty($row['reference']) &&
            Member::where('reference', $row['reference'])->exists()
        ) {
            throw new \Exception('Member with this reference already exists');
        }

        // Check by email
        if (
            isset($row['email']) && !empty($row['email']) &&
            Member::where('email', $row['email'])->exists()
        ) {
            throw new \Exception('Member with this email already exists');
        }

        // Check by national ID
        if (
            isset($row['national_id_number']) && !empty($row['national_id_number']) &&
            Member::where('national_id_number', $row['national_id_number'])->exists()
        ) {
            throw new \Exception('Member with this national ID number already exists');
        }
    }

    private function parsePhone($phone)
    {
        if (empty($phone)) {
            return null;
        }

        $phone = trim((string) $phone);
        $cleaned = preg_replace('/[^\d\+]/', '', $phone);

        // Default country code for Rwanda
        $countryCode = '250';

        // Handle different phone formats
        if (Str::startsWith($cleaned, '250')) {
            $number = substr($cleaned, 3);
            $code = '250';
        } elseif (Str::startsWith($cleaned, '07')) {
            $number = substr($cleaned, 1);
            $code = '250';
        } elseif (Str::startsWith($cleaned, '7')) {
            $number = $cleaned;
            $code = '250';
        } elseif (Str::startsWith($cleaned, '+')) {
            $cleaned = ltrim($cleaned, '+');
            if (Str::startsWith($cleaned, '250')) {
                $number = substr($cleaned, 3);
                $code = '250';
            } else {
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
            'code' => $code ? '+' . $code : null,
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

            $dateStr = (string) $date;

            // Try to parse as year only (e.g., 1990)
            if (preg_match('/^\d{4}$/', $dateStr)) {
                return $dateStr . '-01-01';
            }

            // Try to parse as Carbon date
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$date}. Error: " . $e->getMessage());
            return null;
        }
    }

    private function createMember($row, $nameParts, $gender, $phone)
    {
        $memberData = [
            'reference' => $row['reference'],
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'gender' => $gender,
            'date_of_birth' => $this->parseDate($row['date_of_birth'] ?? null),
            'email' => $row['email'] ?? null,
            'national_id_number' => $this->parseNationalId($row['national_id_number'] ?? null),
            'address' => $row['address'] ?? null,
            'created_by_id' => $this->user->id,
            'branch_id' => $this->branch->id,
        ];

        // Add phone if available
        if ($phone) {
            $memberData['phone'] = [
                'code' => $phone['code'],
                'number' => $phone['number']
            ];
        }

        $member = Member::create($memberData);

        // Handle individual member subscription
        if ($this->type === 'individual') {
            $this->handleMemberSubscription($member, $row);
        }
        // Handle corporate member subscription
        else if ($this->type === 'corporate' && $this->companySubscriptionId) {
            $this->handleCompanySubscription($member);
        }

        return $member;
    }

    /**
     * Handle individual member subscription creation
     */
    private function handleMemberSubscription($member, $row)
    {
        if (!$this->planId) {
            Log::warning('No plan ID provided for individual member import', [
                'member_id' => $member->id,
                'import_id' => $this->importId
            ]);
            return;
        }

        // Get the plan with duration type
        $plan = \App\Models\Plan\Plan::with('duration_type')->find($this->planId);

        if (!$plan) {
            throw new \Exception("Plan not found with ID: {$this->planId}");
        }

        // Parse start date (from row or use today)
        $startDate = isset($row['membership_start_date'])
            ? $this->parseDate($row['membership_start_date'])
            : Carbon::now()->format('Y-m-d');

        // Convert to Carbon instance for date manipulation
        $startDateCarbon = Carbon::parse($startDate);

        // Calculate end date based on plan duration and duration type
        $endDate = $this->calculateEndDate($startDateCarbon, $plan->duration, $plan->duration_type);

        // Create member subscription
        try {
            $memberSubscription = \App\Models\Member\MemberSubscription::create([
                'member_id' => $member->id,
                'plan_id' => $this->planId,
                'start_date' => $startDateCarbon->format('Y-m-d H:i:s'),
                'end_date' => $endDate ? $endDate->format('Y-m-d H:i:s') : null,
                'notes' => 'Imported via member import',
                'created_by_id' => $this->user->id,
                'branch_id' => $this->branch->id,
                'status' => 'pending', // Default status as pending
            ]);

            Log::info('Member subscription created successfully', [
                'member_subscription_id' => $memberSubscription->id,
                'member_id' => $member->id,
                'plan_id' => $this->planId
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Failed to create member subscription: " . $e->getMessage());
        }
    }

    /**
     * Handle corporate member subscription (company subscription member)
     */
    private function handleCompanySubscription($member)
    {
        if (!$this->companySubscriptionId) {
            Log::warning('No company subscription ID provided for corporate member import', [
                'member_id' => $member->id,
                'import_id' => $this->importId
            ]);
            return;
        }

        // Get the company subscription
        $companySubscription = \App\Models\Company\CompanySubscription::find($this->companySubscriptionId);

        if (!$companySubscription) {
            throw new \Exception("Company subscription not found with ID: {$this->companySubscriptionId}");
        }

        // Check if member already exists in this company subscription
        $existingMember = \App\Models\Company\CompanySubscriptionMember::where([
            'company_subscription_id' => $this->companySubscriptionId,
            'member_id' => $member->id
        ])->first();

        if ($existingMember) {
            // If exists, just activate if inactive
            if ($existingMember->status === 'inactive') {
                $existingMember->update(['status' => 'active']);

                Log::info('Existing company subscription member activated', [
                    'company_subscription_member_id' => $existingMember->id,
                    'member_id' => $member->id
                ]);
            } else {
                Log::info('Member already exists in company subscription and is active', [
                    'member_id' => $member->id,
                    'company_subscription_id' => $this->companySubscriptionId
                ]);
            }
        } else {
            // Create new company subscription member
            try {
                $companySubscriptionMember = \App\Models\Company\CompanySubscriptionMember::create([
                    'company_subscription_id' => $this->companySubscriptionId,
                    'member_id' => $member->id,
                    'status' => 'active', // Active by default for corporate members
                ]);

                Log::info('Company subscription member created successfully', [
                    'company_subscription_member_id' => $companySubscriptionMember->id,
                    'member_id' => $member->id,
                    'company_subscription_id' => $this->companySubscriptionId
                ]);
            } catch (\Exception $e) {
                throw new \Exception("Failed to create company subscription member: " . $e->getMessage());
            }
        }
    }

    /**
     * Calculate end date based on duration and duration type
     */
    private function calculateEndDate($startDate, $duration, $durationType)
    {
        if (!$duration || !$durationType) {
            return null;
        }

        $unit = $durationType->unit; // 'days', 'weeks', 'months', 'years'

        switch ($unit) {
            case 'days':
                return $startDate->copy()->addDays($duration);
            case 'weeks':
                return $startDate->copy()->addWeeks($duration);
            case 'months':
                return $startDate->copy()->addMonths($duration);
            case 'years':
                return $startDate->copy()->addYears($duration);
            default:
                return null;
        }
    }

    private function parseNationalId($nationalId)
    {
        if (empty($nationalId)) {
            return null;
        }

        $nationalId = (string) $nationalId;
        return trim($nationalId);
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
