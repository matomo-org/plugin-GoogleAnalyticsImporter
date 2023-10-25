<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter;

if (\class_exists('Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Client' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Client', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Service' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service', 'Google\\AccessToken\\Revoke' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AccessToken_Revoke', 'Google\\AccessToken\\Verify' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AccessToken_Verify', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Model' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Model', 'Google\\Utils\\UriTemplate' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Utils_UriTemplate', 'Google\\AuthHandler\\Guzzle6AuthHandler' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AuthHandler_Guzzle6AuthHandler', 'Google\\AuthHandler\\Guzzle7AuthHandler' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AuthHandler_Guzzle7AuthHandler', 'Google\\AuthHandler\\Guzzle5AuthHandler' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AuthHandler_Guzzle5AuthHandler', 'Google\\AuthHandler\\AuthHandlerFactory' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_AuthHandler_AuthHandlerFactory', 'Google\\Http\\Batch' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Http_Batch', 'Google\\Http\\MediaFileUpload' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Http_MediaFileUpload', 'Google\\Http\\REST' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Http_REST', 'Google\\Task\\Retryable' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Task_Retryable', 'Google\\Task\\Exception' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Task_Exception', 'Google\\Task\\Runner' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Task_Runner', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Collection' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Collection', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Service\\Exception' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service_Exception', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Service\\Resource' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Service_Resource', 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\Exception' => 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \Google\Task\Composer
{
}
/** @phpstan-ignore-next-line */
if (\false) {
    class Google_AccessToken_Revoke extends \Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle5AuthHandler extends \Google\AuthHandler\Guzzle5AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \Google\AuthHandler\Guzzle7AuthHandler
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
    class Google_Http_Batch extends \Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \Google\Http\REST
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
    class Google_Task_Exception extends \Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \Google\Utils\UriTemplate
    {
    }
}
