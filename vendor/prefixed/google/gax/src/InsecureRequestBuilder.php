<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\ApiCore;

use Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Psr7\Utils;
use Matomo\Dependencies\GoogleAnalyticsImporter\Psr\Http\Message\UriInterface;
/**
 * @internal
 */
class InsecureRequestBuilder extends RequestBuilder
{
    /**
     * @param string $path
     * @param array $queryParams
     * @return UriInterface
     */
    protected function buildUri(string $path, array $queryParams)
    {
        $uri = Utils::uriFor(sprintf('http://%s%s', $this->baseUri, $path));
        if ($queryParams) {
            $uri = $this->buildUriWithQuery($uri, $queryParams);
        }
        return $uri;
    }
}
