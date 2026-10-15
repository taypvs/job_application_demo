<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Storage\S3StorageService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CvExcelUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_excel_upload_reads_every_sheet_and_stores_the_cv(): void
    {
        $this->mock(S3StorageService::class, function ($mock): void {
            $mock->shouldReceive('put')->once();
        });
        config(['services.openai.key' => null]);

        $path = storage_path('framework/testing-cv.xlsx');
        $spreadsheet = new Spreadsheet;
        $profile = $spreadsheet->getActiveSheet();
        $profile->setTitle('基本情報');
        $profile->setCellValue('A1', '氏名');
        $profile->setCellValue('B1', '山田太郎');
        $profile->setCellValue('A2', 'スキル');
        $profile->setCellValue('B2', 'PHP');

        $empty = $spreadsheet->createSheet();
        $empty->setTitle('空シート');

        $history = $spreadsheet->createSheet();
        $history->setTitle('職歴');
        $history->setCellValue('A1', '会社');
        $history->setCellValue('B1', '役職');
        $history->setCellValue('A2', '株式会社サンプル');
        $history->setCellValue('B2', 'エンジニア');

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $user = User::factory()->create();
        $file = new UploadedFile(
            $path,
            '職務経歴書.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->actingAs($user)
            ->post(route('user.cv.store'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $cv = $user->fresh()->currentCv();
        $this->assertNotNull($cv);
        $this->assertSame('職務経歴書.xlsx', $cv->original_name);
        $this->assertStringContainsString('【基本情報】', $cv->extracted_text);
        $this->assertStringContainsString('山田太郎', $cv->extracted_text);
        $this->assertStringContainsString('【職歴】', $cv->extracted_text);
        $this->assertStringContainsString("株式会社サンプル\tエンジニア", $cv->extracted_text);
        $this->assertStringNotContainsString('空シート', $cv->extracted_text);
        $this->assertTrue(collect($cv->parsed_profile['skills'])->contains(
            fn (array $skill) => $skill['name'] === 'PHP',
        ));

        @unlink($path);
    }
}
