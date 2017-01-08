<?php

return [
    [
        'repository' => \Mindwalk\DocumentRepositories\Fyndiq::class,
        'username' => 'username1',
        'password' => 'password2',
        'recipients' => [
            'example@example.org',
        ],
    ],

    [
        'repository' => \Mindwalk\DocumentRepositories\Fyndiq::class,
        'username' => 'username2',
        'password' => 'password2',
        'recipients' => [
            'example@example.org',
            'example2@example.org',
        ],
    ],
];
