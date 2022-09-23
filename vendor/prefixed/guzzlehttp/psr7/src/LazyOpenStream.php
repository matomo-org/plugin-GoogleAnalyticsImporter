<?php

declare (strict_types=1);
namespace Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Psr7;

use Matomo\Dependencies\GoogleAnalyticsImporter\Psr\Http\Message\StreamInterface;
/**
 * Lazily reads or writes to a file that is opened only after an IO operation
 * take place on the stream.
 */
#[\AllowDynamicProperties]
final class LazyOpenStream implements StreamInterface
{
    use \Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Psr7\StreamDecoratorTrait;
    /** @var string */
    private $filename;
    /** @var string */
    private $mode;
    /**
     * @param string $filename File to lazily open
     * @param string $mode     fopen mode to use when opening the stream
     */
    public function __construct(string $filename, string $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }
    /**
     * Creates the underlying stream lazily when required.
     */
    protected function createStream() : StreamInterface
    {
        return \Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Psr7\Utils::streamFor(\Matomo\Dependencies\GoogleAnalyticsImporter\GuzzleHttp\Psr7\Utils::tryFopen($this->filename, $this->mode));
    }
}
