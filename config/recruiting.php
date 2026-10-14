<?php

return [

    'employment_types' => ['正社員', '契約社員', '業務委託', 'パート'],

    'salary_bands' => [
        'under400' => ['label' => '400万円未満', 'min' => 0, 'max' => 4000000],
        '400-600' => ['label' => '400〜600万円', 'min' => 4000000, 'max' => 6000000],
        '600-800' => ['label' => '600〜800万円', 'min' => 6000000, 'max' => 8000000],
        '800-1000' => ['label' => '800〜1000万円', 'min' => 8000000, 'max' => 10000000],
        'over1000' => ['label' => '1000万円以上', 'min' => 10000000, 'max' => 100000000],
    ],

    'interview_minutes' => 60,
    'interview_step_minutes' => 30,
    'interview_search_days' => 21,

    'known_skills' => [
        'PHP', 'Laravel', 'MySQL', 'JavaScript', 'TypeScript', 'Vue.js', 'React',
        'HTML', 'CSS', 'Java', 'Go', 'Python', 'AWS', 'Docker', 'Kubernetes',
        'Terraform', 'Linux', 'Git', 'SQL', 'Figma', 'Salesforce', 'GCP', 'Azure',
        'Redis', 'Nginx', 'Node.js', 'Next.js', 'CI/CD', 'PostgreSQL',
    ],

];
