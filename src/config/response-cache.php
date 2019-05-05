<?php

return [
    'tags' => [
        'square1-response-cache'
    ],
    'ttl' => 3600,
    'profiles' => [
        'default' => \Square1\ResponseCache\Profiles\DefaultCacheProfile::class,
        //
    ]
];