<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/rpc/status.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Rpc;

class Status
{
    public static $is_initialized = \false;
    public static function initOnce()
    {
        $pool = \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == \true) {
            return;
        }
        \Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Protobuf\Any::initOnce();
        $pool->internalAddGeneratedFile('
�
google/rpc/status.proto
google.rpc"N
Status
code (
message (	%
details (2.google.protobuf.AnyBa
com.google.rpcBStatusProtoPZ7google.golang.org/genproto/googleapis/rpc/status;status��RPCbproto3', \true);
        static::$is_initialized = \true;
    }
}
