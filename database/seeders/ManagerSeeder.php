<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Manager;
use App\Models\ManagerAvailability;
use Illuminate\Database\Seeder;

class ManagerSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all()->keyBy('code');
        $managers = [
            ['email' => 'sato.kenichi@canal.example.jp', 'name' => '佐藤 健一', 'title' => 'エンジニアリングマネージャー', 'code' => 'DEV'],
            ['email' => 'suzuki.misaki@canal.example.jp', 'name' => '鈴木 美咲', 'title' => 'テックリード', 'code' => 'DEV'],
            ['email' => 'takahashi.daisuke@canal.example.jp', 'name' => '高橋 大輔', 'title' => 'インフラマネージャー', 'code' => 'INFRA'],
            ['email' => 'tanaka.yui@canal.example.jp', 'name' => '田中 結衣', 'title' => 'デザインマネージャー', 'code' => 'DESIGN'],
            ['email' => 'ito.sho@canal.example.jp', 'name' => '伊藤 翔', 'title' => '営業マネージャー', 'code' => 'SALES'],
            ['email' => 'watanabe.mari@canal.example.jp', 'name' => '渡辺 真理', 'title' => 'データマネージャー', 'code' => 'DATA'],
        ];

        foreach ($managers as $row) {
            $manager = Manager::updateOrCreate(['email' => $row['email']], [
                'department_id' => $departments[$row['code']]->id,
                'name' => $row['name'],
                'title' => $row['title'],
            ]);

            foreach (range(1, 5) as $weekday) {
                foreach ([['10:00:00', '12:00:00'], ['13:00:00', '18:00:00']] as [$start, $end]) {
                    ManagerAvailability::updateOrCreate(
                        ['manager_id' => $manager->id, 'weekday' => $weekday, 'start_time' => $start],
                        ['end_time' => $end],
                    );
                }
            }
        }
    }
}
