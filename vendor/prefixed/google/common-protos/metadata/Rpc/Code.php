<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/rpc/code.proto
namespace Matomo\Dependencies\GoogleAnalyticsImporter\GPBMetadata\Google\Rpc;

class Code
{
    public static $is_initialized = \false;
    public static function initOnce()
    {
        $pool = \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();
        if (static::$is_initialized == \true) {
            return;
        }
        $pool->internalAddGeneratedFile('
�
google/rpc/code.proto
google.rpc*�
Code
OK 
	CANCELLED
UNKNOWN
INVALID_ARGUMENT
DEADLINE_EXCEEDED
	NOT_FOUND
ALREADY_EXISTS
PERMISSION_DENIED
UNAUTHENTICATED
RESOURCE_EXHAUSTED
FAILED_PRECONDITION	
ABORTED

OUT_OF_RANGE
UNIMPLEMENTED
INTERNAL
UNAVAILABLE
	DATA_LOSSBX
com.google.rpcB	CodeProtoPZ3google.golang.org/genproto/googleapis/rpc/code;code�RPCbproto3', \true);
        static::$is_initialized = \true;
    }
}
