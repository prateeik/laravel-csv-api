<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;
use App\Models\CompanyDuplicate;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Response;

class CSVUploadController extends Controller
{
    protected $storagePath = 'uploads/csv';

    /**
     * Upload CSV, separate duplicates and uniques,
     * insert accordingly, and return categorized JSON.
     */
    public function upload(Request $request)
    {
        // Step 1: Validate CSV file input
        
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid file upload.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Step 2: Save uploaded file
        $file = $request->file('file');
        $path = $file->store($this->storagePath, 'public');
        $fullPath = storage_path('app/public/' . $path);

        // Step 3: Initialize variables
        $handle = fopen($fullPath, 'r');
        $header = fgetcsv($handle, 1000, ',');
        $uniqueRows = [];
        $duplicateRows = [];
        $invalidRows = [];
        $rowCount = 1;
        $seen = [];

        // Step 4: Loop through CSV
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $rowCount++;
            $data = array_combine($header, $row);

            // Validate individual row
            $rowValidator = Validator::make($data, [
                'company_name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone_number' => 'required|regex:/^[0-9]{7,15}$/',
            ]);

            if ($rowValidator->fails()) {
                $invalidRows[] = [
                    'row' => $rowCount,
                    'errors' => $rowValidator->errors()->all(),
                    'data' => $data
                ];
                continue;
            }

            // Create a unique key for deduplication
            $key = strtolower(trim($data['company_name'] . '|' . $data['email'] . '|' . $data['phone_number']));

            // Check duplicate in same CSV
            if (isset($seen[$key])) {
                $duplicateRows[] = [
                    'row' => $rowCount,
                    'type' => 'in-file',
                    'duplicate_of' => $seen[$key],
                    'data' => $data
                ];
                continue;
            }

            // Check if already exists in DB
            $existsInDb = Company::where([
                'company_name' => $data['company_name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number']
            ])->exists();

            if ($existsInDb) {
                $duplicateRows[] = [
                    'row' => $rowCount,
                    'type' => 'database',
                    'data' => $data
                ];
                continue;
            }

            // Mark as unique if all checks passed
            $uniqueRows[] = [
                'company_name' => $data['company_name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'is_duplicate' => false,
                'created_at' => now(),
                'updated_at' => now()
            ];
            $seen[$key] = $rowCount;
        }
        fclose($handle);

        // Step 5: Insert uniques to DB
        if (!empty($uniqueRows)) {
            Company::insert($uniqueRows);
        }

        // Step 6: Insert duplicates to duplicates table
        foreach ($duplicateRows as $dup) {
            $existing = Company::where([
                'company_name' => $dup['data']['company_name'],
                'email'        => $dup['data']['email'],
                'phone_number' => $dup['data']['phone_number']
            ])->first();

            if($existing) {
                CompanyDuplicate::create([
                    'company_id' => $existing ? $existing->id : null, // always set if exists
                    'duplicate_company_name' => $dup['data']['company_name'],
                    'duplicate_email' => $dup['data']['email'],
                    'duplicate_phone_number' => $dup['data']['phone_number']
                ]);
            }
            
        }


        // Step 7: JSON Response
        return response()->json([
            'status' => 'success',
            'file_stored' => $path,
            'summary' => [
                'rows_processed' => $rowCount - 1,
                'unique_count' => count($uniqueRows),
                'duplicate_count' => count($duplicateRows),
                'invalid_count' => count($invalidRows)
            ],
            'unique_records' => $uniqueRows,
            'duplicates' => $duplicateRows,
            'invalid_rows' => $invalidRows
        ]);
    }

    public function export(Request $request)
    {
        $type = $request->query('type', 'duplicates'); // all | unique | duplicates
        $filename = 'export_' . $type . '_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $response = new StreamedResponse(function () use ($type) {
            $handle = fopen('php://output', 'w');

            // Define CSV header
            fputcsv($handle, ['Company Name', 'Email', 'Phone Number', 'Source']);

            // Export type handler
            if ($type === 'duplicates') {
                // Fetch duplicates
                CompanyDuplicate::select('duplicate_company_name as company_name', 'duplicate_email as email', 'duplicate_phone_number as phone_number')
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $row) {
                            fputcsv($handle, [
                                $row->company_name,
                                $row->email,
                                $row->phone_number,
                                'duplicate'
                            ]);
                        }
                    });

            } elseif ($type === 'unique') {
                // Fetch unique records only
                Company::where('is_duplicate', false)
                    ->select('company_name', 'email', 'phone_number')
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $row) {
                            fputcsv($handle, [
                                $row->company_name,
                                $row->email,
                                $row->phone_number,
                                'unique'
                            ]);
                        }
                    });

            } else {
                // Export all (unique + duplicates)
                // Export companies (both unique and those flagged in companies table)
                Company::select('company_name', 'email', 'phone_number', 'is_duplicate')
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $row) {
                            fputcsv($handle, [
                                $row->company_name,
                                $row->email,
                                $row->phone_number,
                                $row->is_duplicate ? 'duplicate' : 'unique'
                            ]);
                        }
                    });

                // Also export entries from the duplicates table
                CompanyDuplicate::select(
                        'duplicate_company_name as company_name',
                        'duplicate_email as email',
                        'duplicate_phone_number as phone_number'
                    )
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $row) {
                            fputcsv($handle, [
                                $row->company_name,
                                $row->email,
                                $row->phone_number,
                                'duplicate'
                            ]);
                        }
                    });
            }

            fclose($handle);
        });

        // Set response headers for CSV download
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
