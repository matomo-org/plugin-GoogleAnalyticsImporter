<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter;

// Don't redefine the functions if included multiple times.
if (!\function_exists('Matomo\\Dependencies\\GoogleAnalyticsImporter\\GuzzleHttp\\describe_type')) {
    require __DIR__ . '/functions.php';
}
