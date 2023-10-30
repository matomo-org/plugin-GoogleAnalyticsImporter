<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter;

// Don't redefine the functions if included multiple times.
if (!\function_exists('Matomo\\Dependencies\\GoogleAnalyticsImporter\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
