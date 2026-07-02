<?php

return [
    [
        'key'   => 'settings.user.users.google_pending',
        'name'  => 'Aprobación usuarios Google',
        'route' => [
            'google-auth.pending.index',
            'google-auth.pending.approve',
        ],
        'sort'  => 4,
    ],
];
