<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Handler;

use Matomo\Dependencies\GoogleAnalyticsImporter\Psr\Http\Message\RequestInterface;
interface CurlFactoryInterface
{
    /**
     * Creates a cURL handle resource.
     *
     * @param RequestInterface $request Request
     * @param array            $options Transfer options
     *
     * @throws \RuntimeException when an option cannot be applied
     */
    public function create(RequestInterface $request, array $options) : \Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Handler\EasyHandle;
    /**
     * Release an easy handle, allowing it to be reused or closed.
     *
     * This function must call unset on the easy handle's "handle" property.
     */
    public function release(\Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Handler\EasyHandle $easy) : void;
}
