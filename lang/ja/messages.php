<?php

return [
    'auth' => [
        'failed' => 'メールアドレスまたはパスワードが正しくありません。',
        'admin_portal' => '求人管理アカウントは求人管理画面からログインしてください: :url',
        'candidate_portal' => '候補者アカウントは求人サイトからログインしてください: :url',
    ],

    'attributes' => [
        'login' => [
            'email' => 'メールアドレス',
            'password' => 'パスワード',
        ],
        'register' => [
            'name' => '氏名',
            'email' => 'メールアドレス',
            'password' => 'パスワード',
        ],
        'cv' => [
            'file' => '職務経歴書',
        ],
        'apply' => [
            'message' => '志望動機',
        ],
        'chat' => [
            'message' => 'メッセージ',
        ],
        'job_filter' => [
            'position' => '職種',
            'location' => '勤務地',
            'salary' => '年収',
            'keyword' => 'キーワード',
        ],
        'job' => [
            'company_id' => '会社',
            'department_id' => '担当部署',
            'title' => '募集タイトル',
            'position' => '職種',
            'employment_type' => '雇用形態',
            'summary' => '概要',
            'description' => '仕事内容',
            'working_hours' => '勤務時間',
            'location' => '勤務地',
            'remote_policy' => '働き方',
            'salary_min_man' => '年収下限',
            'salary_max_man' => '年収上限',
            'status' => '公開状態',
        ],
        'document' => [
            'title' => '資料名',
            'source_type' => '種別',
            'file' => 'ファイル',
            'url' => 'URL',
        ],
    ],

    'validation' => [
        'cv_file' => 'PDF、Word（.docx）、Excel（.xlsx / .xls）、テキストのいずれかを選択してください。',
        'document_mimes' => 'PDF、Word（.docx）、テキストのいずれかを選択してください。',
        'document_file_required' => 'ファイルを選択してください。',
        'document_url_required' => 'URLを入力してください。',
    ],

    'cv' => [
        'saved_ai' => 'アップロードしました。',
        'saved_local' => 'アップロードしました。',
        'saved_no_text' => 'アップロードしました。文字が入ったPDF、Word、またはExcelをご利用ください。',
    ],

    'jobs' => [
        'created' => '求人を作成しました。',
        'updated' => '求人を更新しました。',
        'applied' => '応募を受け付けました。受信箱にも控えを送っています。',
        'analyzed' => 'マッチング分析が完了しました。',
    ],

    'applications' => [
        'cv_required' => '応募の前に職務経歴書をアップロードしてください。',
        'already_applied' => 'この求人には既に応募済みです。',
        'decision_only' => 'この操作では合否のみ更新できます。',
        'decision_locked' => '合否は確定済みのため、変更できません。',
        'decision_already' => '合否は確定済みです。',
        'decision_updated' => '選考ステータスを更新し、候補者へ通知しました。',
        'interview_set' => '面接を設定し、候補者の受信箱へ通知しました。:when / :manager',
    ],

    'interviews' => [
        'closed' => '選考が終了している応募には面接を設定できません。',
        'already_set' => 'すでに面接が設定されています。',
        'no_department' => '求人に担当部署が設定されていません。',
        'no_manager' => 'この職種を担当する部署のマネージャーが見つかりません。',
        'no_slot' => '空いている面接枠が見つかりませんでした。マネージャーの勤務時間を確認してください。',
        'slot_taken' => '選んだ枠が埋まりました。もう一度確定してください。',
    ],

    'documents' => [
        'extract_failed' => 'ファイルを読み取れませんでした。別のファイル、またはURLを指定してください。',
        'pinecone_missing' => '資料を削除できませんでした。',
        'pinecone_delete_failed' => '資料を削除できませんでした。',
        'file_required' => 'ファイルを選択してください。',
        'embed_mismatch' => '資料を登録できませんでした。もう一度お試しください。',
        'note_stored' => '登録しました。',
        'note_index_failed' => '登録できませんでした。もう一度お試しください。',
        'indexed' => '資料を登録しました。',
        'saved' => '資料を登録しました。',
        'deleted' => '資料を削除しました。',
    ],

    'storage' => [
        'save_failed' => 'ファイルの保存に失敗しました。S3の設定を確認してください。',
        'delete_failed' => 'ファイルの削除に失敗しました。S3の設定を確認してください。',
        'get_failed' => 'ファイルの取得に失敗しました。',
    ],

    'files' => [
        'doc_unsupported' => '.doc 形式には対応していません。.docx でアップロードしてください。',
        'unsupported' => '対応していないファイル形式です。',
        'url_failed' => 'URLから内容を取得できませんでした。',
        'pdf_failed' => 'PDFから本文を抽出できませんでした。',
        'word_open_failed' => 'Wordファイルを開けませんでした。',
        'word_empty' => 'Wordファイルの本文が見つかりませんでした。',
        'excel_failed' => 'Excelファイルを読み取れませんでした。',
        'text_failed' => 'テキストファイルを読み取れませんでした。',
    ],

    'matching' => [
        'cv_required' => '職務経歴書をアップロードしてから分析してください。',
        'unavailable' => 'この求人は分析できません。',
        'ai_failed' => 'AI分析に失敗しました。APIキーと通信状況を確認してください。',
        'ai_unreadable' => 'AIの応答を解釈できませんでした。',
        'empty_summary' => '職務経歴書からテキストを抽出できなかったため、マッチ度を判定できません。',
        'empty_weakness' => '職務経歴書の本文が空です。',
        'empty_improvement' => 'テキストを選択できるPDF、または.docxで再アップロードしてください。',
        'skill_hit' => '必須または歓迎スキル「:skill」が職務経歴から確認できます。',
        'skill_miss' => '必須スキル「:skill」が経歴から確認できません。',
        'skill_improve' => '「:skill」の実務経験、または学習内容を職務経歴書に具体的に追記してください。',
        'no_hits' => '経歴本文は登録されています。求人スキルとの一致箇所を追記すると精度が上がります。',
        'local_summary' => 'キーワード照合による参考値です。必須スキルの一致は :required_hit/:required_total 件、歓迎スキルの一致は :nice_hit/:nice_total 件です。',
    ],

    'chat' => [
        'failed' => '回答の生成に失敗しました。時間をおいて再度お試しください。',
        'no_documents' => 'まだ参照資料が登録されていません。求人管理側に資料のアップロードを依頼してください。',
        'no_match' => '登録資料の中に、ご質問に直接対応する記述が見つかりませんでした。別の言い方でお試しください。',
        'extractive_intro' => '資料をもとに、関連する記載をまとめます。',
    ],

    'mailbox' => [
        'applied_subject' => '応募を受け付けました',
        'applied_body' => ":companyの「:title」への応募を受け付けました。\n選考状況は「応募状況」から確認できます。",
        'interview_subject' => '面接日程のご案内',
        'interview_body' => ":companyの「:title」について、面接日程が確定しました。\n\n日時: :when\n担当: :manager（:department / :role）\n形式: :format\n\n当日の接続情報は、前日までにこの受信箱でご連絡します。",
        'decision_subject' => '選考結果のご連絡',
        'passed_body' => "「:title」の選考の結果、次のステップへお進みいただくこととなりました。\n担当者より改めてご連絡します。",
        'rejected_body' => "「:title」の選考の結果、今回は見送りとさせていただきます。\nご応募ありがとうございました。",
        'updated_body' => '「:title」の選考ステータスが「:status」に更新されました。',
    ],
];
