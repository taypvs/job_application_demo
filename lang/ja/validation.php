<?php

return [
    'required' => ':attributeは必須です。',
    'email' => ':attributeは正しいメールアドレスを入力してください。',
    'confirmed' => ':attributeが確認用と一致しません。',
    'unique' => 'この:attributeは既に使われています。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
        'file' => ':attributeは:max KB以下にしてください。',
        'numeric' => ':attributeは:max以下にしてください。',
    ],
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
        'numeric' => ':attributeは:min以上にしてください。',
    ],
    'integer' => ':attributeは整数で入力してください。',
    'gte' => [
        'numeric' => ':attributeは:value以上にしてください。',
    ],
    'mimes' => ':attributeの形式が正しくありません。',
    'url' => ':attributeは正しいURLを入力してください。',
    'in' => ':attributeの値が正しくありません。',
    'exists' => ':attributeが正しくありません。',
];
