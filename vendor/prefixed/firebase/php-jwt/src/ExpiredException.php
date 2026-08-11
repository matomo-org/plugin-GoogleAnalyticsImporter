<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter\Firebase\JWT;

class ExpiredException extends \UnexpectedValueException implements JWTExceptionWithPayloadInterface
{
    /**
     * @var object
     */
    private $payload;
    /**
     * @var int|null
     */
    private $timestamp;
    public function setPayload(object $payload) : void
    {
        $this->payload = $payload;
    }
    public function getPayload() : object
    {
        return $this->payload;
    }
    public function setTimestamp(int $timestamp) : void
    {
        $this->timestamp = $timestamp;
    }
    public function getTimestamp() : ?int
    {
        return $this->timestamp;
    }
}
