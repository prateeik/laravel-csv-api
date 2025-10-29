<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Company;
use App\Models\CompanyDuplicate;

class CSVUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_uploads_and_parses_a_valid_csv_file()
    {
        Storage::fake('app/public/');

        // CSV content: 2 uniques, 1 duplicate, 1 invalid
        $csv = <<<CSV
company_name,email,phone_number
Alpha Tech,alpha@example.com,1234567
Beta Inc,beta@example.com,9876543
Alpha Tech,alpha@example.com,1234567
Invalid Co,invalid-email,not-a-number
CSV;

        $file = UploadedFile::fake()->createWithContent('test.csv', $csv);

        $response = $this->postJson('/api/csv/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'summary' => [
                    'unique_count' => 2,
                    'duplicate_count' => 1,
                    'invalid_count' => 1,
                ],
            ]);

        $this->assertEquals(2, Company::count());
        $this->assertEquals(1, CompanyDuplicate::count());

        // Clean up temp file
        $path = storage_path('app/public/uploads/csv/' . $file->hashName());

        // Your assertions here...

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
