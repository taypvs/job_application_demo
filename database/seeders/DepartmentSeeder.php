<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'DEV', 'name' => '開発部', 'description' => 'Webシステムとプロダクト開発を担当します。'],
            ['code' => 'INFRA', 'name' => 'インフラ部', 'description' => 'クラウド基盤、SRE、運用を担当します。'],
            ['code' => 'DESIGN', 'name' => 'デザイン部', 'description' => 'UI/UXとブランド体験を担当します。'],
            ['code' => 'SALES', 'name' => '営業部', 'description' => '法人顧客への提案と受注を担当します。'],
            ['code' => 'DATA', 'name' => 'データ分析部', 'description' => 'データ分析と意思決定支援を担当します。'],
        ];

        foreach ($rows as $row) {
            Department::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
