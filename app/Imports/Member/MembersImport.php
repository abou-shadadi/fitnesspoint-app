<?php

namespace App\Imports\Member;

use App\Models\Member\Member;
use App\Models\Member\MemberImportLog;
use App\Models\Company\Company;
use App\Models\Branch\Branch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MembersImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected $predefinedColumns = [
        'reference',
        'first_name',
        'last_name',
        'gender',
        'national_id_number',
        'date_of_birth',
        'phone_code',
        'phone_number',
        'email',
        'address',
        'status',
    ];

    public $company, $branch, $user, $importId;
    protected $failedRows = [];

    public function __construct($companyId = null, $branchId, $userId, $importId = null)
    {
        if ($companyId) {
            $this->company = Company::find($companyId);
            if (!$this->company) {
                throw new \Exception('Company not found');
            }
        }

        $this->branch = Branch::find($branchId);
        if (!$this->branch) {
            throw new \Exception('Branch not found');
        }

        $this->user = User::find($userId);
        if (!$this->user) {
            throw new \Exception('User not found');
        }

        $this->importId = $importId;
    }

    public function collection(Collection $rows)
    {
        Log::info('Starting Member Import Collection...');
        DB::beginTransaction();
        try {
            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber === 0) continue; // Skip header row
                $this->processRow($row, $rowNumber);
            }
            DB::commit();
            Log::info('Member import completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Member import failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processRow($row, $rowNumber)
    {
        $importComment = '';
        Log::info("Processing row {$rowNumber}: " . json_encode($row));

        try {
            // Perform validation
            $this->validateRow($row, $rowNumber);

            // Process gender
            $gender = $this->processGender($row);

            // Generate reference if empty
            if (empty($row['reference'])) {
                $row['reference'] = $this->generateMemberReference();
            }

            // Check if member exists
            $this->checkMemberExistence($row);

            // Create member
            $member = $this->createMember($row, $gender);

            Log::info("Successfully created member: {$member->reference}");

        } catch (\Exception $e) {
            $this->handleRowError($row, $rowNumber, $e->getMessage(), $importComment);
            throw $e; // Stop processing this row
        }
    }

    private function handleRowError($row, $rowNumber, $errorMessage, $importComment)
    {
        $row['row'] = $rowNumber + 1;
        $row['column'] = $this->getColumnFromError($errorMessage);
        $row['comment'] = $importComment ?: $errorMessage;

        Log::error("Error in row {$row['row']}, column {$row['column']}: {$row['comment']}");
        $this->logFailedRow($row, $rowNumber, $errorMessage);
    }

    private function logFailedRow($row, $rowNumber, $errorMessage)
    {
        if ($this->importId) {
            MemberImportLog::create([
                'log_message' => "Error in row $rowNumber: $errorMessage",
                'member_import_id' => $this->importId,
                'is_resolved' => false,
                'data' => $row,
            ]);
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,Male,Female,M,F',
            'date_of_birth' => 'nullable|date|before:today',
            'email' => 'nullable|email|unique:members,email',
            'national_id_number' => 'nullable|string|unique:members,national_id_number',
            'reference' => 'nullable|string|unique:members,reference',
            'status' => 'nullable|in:active,inactive,Active,Inactive',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.first_name.required' => 'First name is required in row :row.',
            '*.last_name.required' => 'Last name is required in row :row.',
            '*.gender.required' => 'Gender is required in row :row.',
            '*.gender.in' => 'Gender must be Male or Female in row :row.',
            '*.date_of_birth.date' => 'Date of birth must be a valid date in row :row.',
            '*.date_of_birth.before' => 'Date of birth must be before today in row :row.',
            '*.email.email' => 'Email must be a valid email address in row :row.',
            '*.email.unique' => 'Email already exists in row :row.',
            '*.national_id_number.unique' => 'National ID number already exists in row :row.',
            '*.reference.unique' => 'Reference number already exists in row :row.',
            '*.status.in' => 'Status must be Active or Inactive in row :row.',
        ];
    }

    private function validateRow($row, $rowNumber)
    {
        // Define the fields that are expected to be non-empty
        $requiredFields = ['first_name', 'last_name', 'gender'];

        // Check if any of the required fields in the row are not empty
        $notEmptyFields = collect($requiredFields)->filter(function ($field) use ($row) {
            return !empty($row[$field]);
        });

        // If at least one required field is not empty, perform validation
        if ($notEmptyFields->isNotEmpty()) {
            $validator = Validator::make($row->toArray(), $this->rules(), $this->customValidationMessages());
            if ($validator->fails()) {
                throw new ValidationException($validator, "Validation failed in row " . ($rowNumber + 2));
            }
        }
    }

    private function processGender($row)
    {
        if (!isset($row['gender'])) {
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

    private function createMember($row, $gender)
    {
        // Prepare phone data
        $phone = null;
        if (!empty($row['phone_code']) && !empty($row['phone_number'])) {
            $phone = [
                'code' => $this->formatPhoneCode($row['phone_code']),
                'number' => $this->formatPhoneNumber($row['phone_number'])
            ];
        }

        // Prepare member data
        $memberData = [
            'reference' => $row['reference'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'gender' => $gender,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'email' => $row['email'] ?? null,
            'national_id_number' => $row['national_id_number'] ?? null,
            'phone' => $phone,
            'address' => $row['address'] ?? null,
            'status' => isset($row['status']) ? strtolower($row['status']) : 'active',
            'created_by_id' => $this->user->id,
        ];

        // Add company and branch if applicable
        if ($this->company) {
            $memberData['company_id'] = $this->company->id;
        }

        if ($this->branch) {
            $memberData['branch_id'] = $this->branch->id;
        }

        // Create the member
        return Member::create($memberData);
    }

    private function formatPhoneCode($code)
    {
        // Ensure phone code starts with +
        $code = trim($code);
        if (!Str::startsWith($code, '+')) {
            $code = '+' . $code;
        }
        return $code;
    }

    private function formatPhoneNumber($number)
    {
        // Remove all non-digit characters
        return preg_replace('/\D/', '', $number);
    }

    private function generateMemberReference()
    {
        do {
            $reference = 'MBR-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Member::where('reference', $reference)->exists());

        return $reference;
    }

    private function getColumnFromError($errorMessage)
    {
        preg_match('/column: (\w+)/', $errorMessage, $matches);
        return $matches[1] ?? 'unknown';
    }

    public function getFailedRows()
    {
        return $this->failedRows;
    }
}
