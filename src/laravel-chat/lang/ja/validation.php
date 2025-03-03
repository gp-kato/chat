<?php

return [
    'required' => ':attribute は必須です。',
    'email' => ':attribute は有効なメールアドレスである必要があります。',
    'max' => [
        'string' => ':attribute は :max 文字以内で入力してください。',
    ],
    'min' => [
        'string' => ':attribute は :min 文字以上で入力してください。',
    ],
    'unique' => ':attribute は既に使用されています。',
    'confirmed' => ':attribute が確認用と一致しません。',
    'numeric' => ':attribute は数値である必要があります。',
    
    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'description' => '説明文',
    ],
];
