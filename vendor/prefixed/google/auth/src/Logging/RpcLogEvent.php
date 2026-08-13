<?php

/**
 * Copyright 2024 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Matomo\Dependencies\GoogleAnalyticsImporter\Google\Auth\Logging;

/**
 * A class that contains all the required information for logging.
 *
 * @internal
 */
class RpcLogEvent
{
    /**
     * Timestamp in format RFC3339 representing when this event ocurred
     *
     * @var string
     */
    public $timestamp;
    /**
     * The time in milliseconds at time on creation for calculating latency
     *
     * @var float
     */
    public $milliseconds;
    /**
     * Rest method type
     *
     * @var null|string
     */
    public $method = null;
    /**
     * URL representing the rest URL endpoint
     *
     * @var null|string
     */
    public $url = null;
    /**
     * An array that contains the headers for the response or request
     *
     * @var null|array<mixed>
     */
    public $headers = null;
    /**
     * An array representation of JSON for the response or request
     *
     * @var null|string
     */
    public $payload = null;
    /**
     * Status code for REST or gRPC methods
     *
     * @var null|int|string
     */
    public $status = null;
    /**
     * The latency in milliseconds
     *
     * @var null|int
     */
    public $latency = null;
    /**
     * The retry attempt number
     *
     * @var null|int
     */
    public $retryAttempt = null;
    /**
     * The name of the gRPC method being called
     *
     * @var null|string
     */
    public $rpcName = null;
    /**
     * The Service Name of the gRPC
     *
     * @var null|string $serviceName
     */
    public $serviceName = null;
    /**
     * The Process ID for tracing logs
     *
     * @var null|int $processId
     */
    public $processId = null;
    /**
     * The Request id for tracing logs
     *
     * @var null|int $requestId;
     */
    public $requestId = null;
    /**
     * Creates an object with all the fields required for logging
     * Passing a string representation of a timestamp calculates the difference between
     * these two times and sets the latency field with the result.
     *
     * @param null|float $startTime (Optional) Parameter to calculate the latency
     */
    public function __construct(?float $startTime = null)
    {
        $this->timestamp = date(\DATE_RFC3339);
        // Takes the micro time and convets it to millis
        $this->milliseconds = round(microtime(\true) * 1000);
        if ($startTime) {
            $this->latency = (int) round($this->milliseconds - $startTime);
        }
    }
}
