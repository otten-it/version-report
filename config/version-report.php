<?php

// otten-it/version-report: lets the Otten IT hosting dashboard read this application's
// versions. Disabled until VERSION_REPORT_TOKEN is set.
return [
    // Shared secret, sent by the dashboard as "Authorization: Bearer <token>". Generate with
    // `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` and enter the same value in the
    // dashboard. Empty: the endpoint does not exist (404).
    'token' => env('VERSION_REPORT_TOKEN'),

    // URL path of the endpoint.
    'path' => 'version-report',
];
