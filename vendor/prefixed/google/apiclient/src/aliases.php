<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter;

if (\class_exists('Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Client' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Client', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Service' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\AccessToken\\Revoke' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AccessToken_Revoke', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\AccessToken\\Verify' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AccessToken_Verify', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Model' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Model', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Utils\\UriTemplate' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Utils_UriTemplate', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AuthHandler_Guzzle6AuthHandler', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AuthHandler_Guzzle7AuthHandler', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\AuthHandler\\AuthHandlerFactory' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AuthHandler_AuthHandlerFactory', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Http\\Batch' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Http_Batch', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Http\\MediaFileUpload' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Http_MediaFileUpload', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Http\\REST' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Http_REST', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Task\\Retryable' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Task_Retryable', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Task\\Exception' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Task_Exception', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Task\\Runner' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Task_Runner', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Collection' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Collection', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Service\\Exception' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service_Exception', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Service\\Resource' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service_Resource', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Exception' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Task\Composer
{
}
/** @phpstan-ignore-next-line */
if (\false) {
    class Google_AccessToken_Revoke extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Client
    {
    }
    class Google_Collection extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Collection
    {
    }
    class Google_Exception extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Exception
    {
    }
    class Google_Http_Batch extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Http\REST
    {
    }
    class Google_Model extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Model
    {
    }
    class Google_Service extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service
    {
    }
    class Google_Service_Exception extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Utils\UriTemplate
    {
    }
}
