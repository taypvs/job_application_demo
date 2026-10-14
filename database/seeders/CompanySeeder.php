<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(['name' => '株式会社カナル'], [
            'industry' => 'IT・インターネット',
            'description' => '業務システムの企画、設計、開発、運用を一気通貫で行う事業会社です。自社プロダクト「Canal Works」と、顧客向けの受託開発を両輪で進めています。少人数のチームで裁量を持って開発できる環境です。',
            'website' => 'https://canal.example.jp',
            'employee_count' => 180,
            'founded_year' => 2014,
            'headquarters' => '東京都港区南青山3-1-1',
        ]);
    }
}
