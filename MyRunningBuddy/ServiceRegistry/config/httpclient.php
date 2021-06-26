<?php

return [
    'timeout' => env('HTTP_CLIENT_TIMEOUT_IN_SECONDS', 3),
    'retries' => env('HTTP_CLIENT_RETRIES_BEFORE_FAIL', 2)
];
