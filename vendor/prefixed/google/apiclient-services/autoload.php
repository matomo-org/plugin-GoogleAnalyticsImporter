<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter;

// For older (pre-2.7.2) verions of google/apiclient
if (\file_exists(__DIR__ . '/../apiclient/src/Google/Client.php') && !\class_exists('Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Client', \false)) {
    require_once __DIR__ . '/../apiclient/src/Google/Client.php';
    if (\defined('Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Client::LIBVER') && \version_compare(Google_Client::LIBVER, '2.7.2', '<=')) {
        $servicesClassMap = ['Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Client' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Client', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Service' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Service\\Resource' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service_Resource', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Model' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Model', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Collection' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Collection'];
        foreach ($servicesClassMap as $alias => $class) {
            \class_alias($class, $alias);
        }
    }
}
\spl_autoload_register(function ($class) {
    $class = preg_replace('/^Piwik\\\\Dependencies\\\\/', 'Matomo\\Dependencies\\', $class);

    if (0 === \strpos($class, 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service_')) {
        // Autoload the new class, which will also create an alias for the
        // old class by changing underscores to namespaces:
        //     Google_Service_Speech_Resource_Operations
        //      => Google\Service\Speech\Resource\Operations
        $classExists = \class_exists($newClass = \str_replace('_', '\\', $class));
        if ($classExists) {
            return \true;
        }
    }
}, \true, \true);
