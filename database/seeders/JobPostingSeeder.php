<?php

namespace Database\Seeders;

use App\Enums\JobStatus;
use App\Enums\SkillCategory;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobPosting;
use Illuminate\Database\Seeder;

class JobPostingSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', '株式会社カナル')->firstOrFail();
        $departments = Department::all()->keyBy('code');
        $common = [
            'employment_type' => '正社員',
            'working_hours' => '9:30〜18:30（休憩60分、フレックスタイム制）',
            'salary_note' => '経験・能力を考慮して決定します。賞与年2回。',
            'benefits' => "各種社会保険完備\n交通費支給（上限月5万円）\nリモートワーク手当\n書籍購入補助、資格取得支援",
            'holidays' => '完全週休二日制（土日）、国民の祝日、年末年始、夏季休暇、有給休暇、慶弔休暇',
            'probation' => '3ヶ月（条件の変更なし）',
            'selection_process' => "書類選考\n一次面接（担当マネージャー）\n最終面接\n内定",
            'status' => JobStatus::Published,
        ];

        $jobs = [
            [
                'title' => 'Webシステムエンジニア',
                'position' => 'Webシステムエンジニア',
                'code' => 'DEV',
                'location' => '東京都港区',
                'remote_policy' => 'hybrid',
                'salary_min' => 5000000,
                'salary_max' => 8000000,
                'summary' => '自社プロダクトと受託案件のWebシステムを、要件定義からリリースまで担当するエンジニアを募集します。',
                'description' => "LaravelとVue.jsを中心に、業務Webシステムの設計・実装・テストを行います。\n既存機能の改善と、新規モジュールの立ち上げをチームで分担します。\nコードレビューと障害対応にも参加し、運用まで見ます。",
                'background' => '受託と自社プロダクトの案件が増え、実装をリードできるメンバーが不足しています。',
                'requirements' => "Webアプリケーションの開発経験3年以上\nPHPまたは同等のサーバーサイド言語の実務経験\nチームでのGitを使った開発経験",
                'appeal' => '企画から運用まで一通り経験できます。小規模チームのため、技術選定にも関われます。',
                'required' => ['PHP', 'Laravel', 'MySQL', 'JavaScript'],
                'nice' => ['Vue.js', 'AWS', 'Docker'],
            ],
            [
                'title' => 'Laravelバックエンドエンジニア',
                'position' => 'バックエンドエンジニア',
                'code' => 'DEV',
                'location' => '東京都港区',
                'remote_policy' => 'hybrid',
                'salary_min' => 6000000,
                'salary_max' => 9000000,
                'summary' => 'Canal WorksのAPIとバッチ処理を設計・実装するバックエンドエンジニアを募集します。',
                'description' => "REST APIの設計、Eloquentを使ったデータモデル、キューによる非同期処理を担当します。\n性能が落ちたクエリの見直しや、認可の整理も業務に含まれます。\nフロントエンド担当とOpenAPIを共有しながら進めます。",
                'background' => 'APIの利用者が増え、ドメイン分割と性能改善を進める体制が必要です。',
                'requirements' => "PHP / Laravelでの開発経験3年以上\nRDBのテーブル設計経験\n自動テストを書いた経験",
                'appeal' => 'ドメインの境界を自分で提案できます。レガシーの全面書き換えではなく、段階的な改善です。',
                'required' => ['PHP', 'Laravel', 'MySQL', 'Git'],
                'nice' => ['Redis', 'AWS', 'Docker'],
            ],
            [
                'title' => 'フロントエンドエンジニア',
                'position' => 'フロントエンドエンジニア',
                'code' => 'DEV',
                'location' => '東京都港区',
                'remote_policy' => 'remote',
                'salary_min' => 5500000,
                'salary_max' => 8500000,
                'summary' => '管理画面と顧客向け画面のUI実装を担当するフロントエンドエンジニアを募集します。フルリモート可です。',
                'description' => "TypeScriptとVue.jsで画面を実装し、デザイン部と仕様を詰めます。\nアクセシビリティと表示速度もレビュー対象です。\nコンポーネントの共通化を進め、画面追加のコストを下げます。",
                'background' => '画面数が増え、サーバーサイド兼任ではUI品質を保てなくなっています。',
                'requirements' => "JavaScriptまたはTypeScriptの実務経験2年以上\nHTML / CSSでの画面実装経験\nデザイナーやバックエンドとの協業経験",
                'appeal' => 'フルリモートが基本です。出社は月1回の定例のみです。',
                'required' => ['TypeScript', 'Vue.js', 'HTML', 'CSS'],
                'nice' => ['React', 'Figma', 'Next.js'],
            ],
            [
                'title' => 'インフラエンジニア',
                'position' => 'インフラエンジニア',
                'code' => 'INFRA',
                'location' => '大阪府大阪市',
                'remote_policy' => 'onsite',
                'salary_min' => 6000000,
                'salary_max' => 9500000,
                'summary' => '大阪拠点で、AWS上の本番環境の構築と運用を担当するインフラエンジニアを募集します。',
                'description' => "VPC、ECS、RDSの構成管理と、監視・バックアップの整備を行います。\n開発部からの環境依頼に応え、Terraformで再現できる状態にします。\n夜間の一次対応は当番制です。",
                'background' => '大阪の顧客案件が増え、現地で基盤を見られる担当者が必要です。',
                'requirements' => "Linuxサーバーの構築・運用経験3年以上\nAWSの実務経験\nネットワークの基礎知識",
                'appeal' => '大阪オフィスは駅から徒歩5分です。検証用のAWSアカウントを個人で使えます。',
                'required' => ['AWS', 'Linux', 'Docker', 'Terraform'],
                'nice' => ['Nginx', 'MySQL', 'CI/CD'],
            ],
            [
                'title' => 'SRE',
                'position' => 'SRE',
                'code' => 'INFRA',
                'location' => '東京都港区',
                'remote_policy' => 'hybrid',
                'salary_min' => 7000000,
                'salary_max' => 11000000,
                'summary' => 'サービスの可用性とリリース速度を同時に上げるSREを募集します。',
                'description' => "SLI/SLOの定義、障害のポストモーテム、デプロイパイプラインの改善を担当します。\nKubernetes上のワークロードのリソース設計も行います。\n開発チームにオブザーバビリティの使い方を広めます。",
                'background' => 'リリース頻度が上がり、属人的な本番作業を減らせていません。',
                'requirements' => "Webサービスの運用経験3年以上\nCI/CDまたはコンテナの実務経験\n障害対応の経験",
                'appeal' => 'オンコールは月4日までです。トイル削減の提案が評価対象になります。',
                'required' => ['Kubernetes', 'AWS', 'Docker', 'CI/CD'],
                'nice' => ['Terraform', 'Go', 'Linux'],
            ],
            [
                'title' => 'UI/UXデザイナー',
                'position' => 'UI/UXデザイナー',
                'code' => 'DESIGN',
                'location' => '東京都港区',
                'remote_policy' => 'hybrid',
                'salary_min' => 4500000,
                'salary_max' => 7500000,
                'summary' => '業務システムの情報設計と画面デザインを担当するUI/UXデザイナーを募集します。',
                'description' => "利用者インタビュー、画面遷移、FigmaでのUI作成、開発への仕様渡しを行います。\n既存画面の使い勝手の改善も継続的に担当します。\nデザインシステムのコンポーネントを増やします。",
                'background' => '機能追加が先行し、画面ごとの操作が揃っていない状態です。',
                'requirements' => "Webまたは業務システムのUIデザイン経験2年以上\nFigmaが使えること\nエンジニアと仕様を詰めた経験",
                'appeal' => '見た目だけでなく、業務フローの提案まで任せます。',
                'required' => ['Figma', 'HTML', 'CSS'],
                'nice' => ['JavaScript', 'TypeScript'],
            ],
            [
                'title' => 'プロダクトマネージャー',
                'position' => 'プロダクトマネージャー',
                'code' => 'DEV',
                'location' => '東京都港区',
                'remote_policy' => 'hybrid',
                'salary_min' => 8000000,
                'salary_max' => 12000000,
                'summary' => 'Canal Worksのロードマップと優先順位を決めるプロダクトマネージャーを募集します。',
                'description' => "顧客の業務課題をヒアリングし、リリース単位に分割します。\n開発、デザイン、営業と週次で優先度を揃えます。\nリリース後の利用状況を見て、次の改善を決めます。",
                'background' => '要望の入口が分散し、何を先に作るかの判断が遅くなっています。',
                'requirements' => "BtoBプロダクトまたは業務システムの企画経験3年以上\nエンジニアと仕様を合意した経験\n優先順位を言語化できること",
                'appeal' => '売上と開発の両方の数字を見て判断できます。',
                'required' => ['SQL'],
                'nice' => ['Figma', 'Salesforce'],
            ],
            [
                'title' => '法人営業',
                'position' => '法人営業',
                'code' => 'SALES',
                'location' => '愛知県名古屋市',
                'remote_policy' => 'onsite',
                'salary_min' => 4000000,
                'salary_max' => 7000000,
                'summary' => '東海エリアの法人顧客に対し、業務システムの提案と導入支援を行う営業を募集します。',
                'description' => "既存顧客の更新と、新規の課題ヒアリングを担当します。\n提案資料は開発部と一緒に作ります。\n受注後は導入のキックオフまで同席します。",
                'background' => '名古屋の問い合わせが増え、現地で継続フォローできる担当者が必要です。',
                'requirements' => "法人営業の経験2年以上\nITまたは無形商材の提案経験\n普通自動車免許があれば尚可",
                'appeal' => 'インセンティブは四半期評価です。開発同席があるため、実現できない提案を一人で抱えません。',
                'required' => ['Salesforce'],
                'nice' => ['SQL'],
            ],
            [
                'title' => 'データアナリスト',
                'position' => 'データアナリスト',
                'code' => 'DATA',
                'location' => '福岡県福岡市',
                'remote_policy' => 'remote',
                'salary_min' => 5000000,
                'salary_max' => 8000000,
                'summary' => 'プロダクトの利用ログと売上データを分析し、意思決定の材料を作るアナリストを募集します。',
                'description' => "SQLで集計し、週次の指標レポートを作成します。\n異常値の原因を開発部と確認します。\n分析用のテーブル定義も提案します。",
                'background' => '数字の依頼が個人チャットに散らばり、同じ集計が繰り返されています。',
                'requirements' => "SQLでの集計経験2年以上\nビジネス側へ分析結果を説明した経験\n表計算またはBIツールの利用経験",
                'appeal' => '福岡またはフルリモートで勤務できます。分析基盤はこれから整える段階です。',
                'required' => ['SQL', 'Python'],
                'nice' => ['PostgreSQL', 'AWS'],
            ],
            [
                'title' => 'QAエンジニア',
                'position' => 'QAエンジニア',
                'code' => 'DEV',
                'location' => '東京都港区',
                'remote_policy' => 'hybrid',
                'salary_min' => 4500000,
                'salary_max' => 7000000,
                'summary' => 'リリース前の試験設計と、自動テストの不足箇所を埋めるQAエンジニアを募集します。',
                'description' => "仕様からテスト観点を作り、受入条件を開発と合意します。\n回帰テストのうち繰り返すものを自動化します。\n障害チケットの再現手順を整理します。",
                'background' => '機能増加に対して、手動確認の時間がリリースのボトルネックになっています。',
                'requirements' => "Webシステムのテスト経験2年以上\n不具合を再現手順つきで報告できること\n基本的なSQLまたはAPIの確認ができること",
                'appeal' => 'テスト専門として評価されます。開発チケットの実装は必須ではありません。',
                'required' => ['SQL', 'Git'],
                'nice' => ['JavaScript', 'Docker', 'CI/CD'],
            ],
        ];

        foreach ($jobs as $row) {
            $job = JobPosting::updateOrCreate(['title' => $row['title']], array_merge($common, [
                'company_id' => $company->id,
                'department_id' => $departments[$row['code']]->id,
                'title' => $row['title'],
                'position' => $row['position'],
                'location' => $row['location'],
                'remote_policy' => $row['remote_policy'],
                'salary_min' => $row['salary_min'],
                'salary_max' => $row['salary_max'],
                'summary' => $row['summary'],
                'description' => $row['description'],
                'background' => $row['background'],
                'requirements' => $row['requirements'],
                'appeal' => $row['appeal'],
                'published_at' => now(),
            ]));

            $job->skills()->delete();
            $order = 0;
            foreach ($row['required'] as $name) {
                $job->skills()->create([
                    'name' => $name,
                    'category' => SkillCategory::Required,
                    'sort_order' => $order++,
                ]);
            }
            foreach ($row['nice'] as $name) {
                $job->skills()->create([
                    'name' => $name,
                    'category' => SkillCategory::NiceToHave,
                    'sort_order' => $order++,
                ]);
            }
        }
    }
}
