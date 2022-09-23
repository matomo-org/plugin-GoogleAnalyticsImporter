<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise;

final class Is
{
    /**
     * Returns true if a promise is pending.
     *
     * @return bool
     */
    public static function pending(\Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled or rejected.
     *
     * @return bool
     */
    public static function settled(\Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() !== \Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled.
     *
     * @return bool
     */
    public static function fulfilled(\Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\PromiseInterface::FULFILLED;
    }
    /**
     * Returns true if a promise is rejected.
     *
     * @return bool
     */
    public static function rejected(\Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Promise\PromiseInterface::REJECTED;
    }
}
