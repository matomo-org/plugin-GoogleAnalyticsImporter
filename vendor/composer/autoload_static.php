<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc47ec56ef29ebf649dc1fc01ed66ce2d
{
    public static $files = array(
);


    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib3\\' => 11,
        ),
        'b' => 
        array (
            'bcmath_compat\\' => 14,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Client\\' => 16,
            'Psr\\Cache\\' => 10,
            'ParagonIE\\ConstantTime\\' => 23,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
            'Grpc\\Gcp\\' => 9,
            'Grpc\\' => 5,
            'Google\\Service\\' => 15,
            'Google\\Protobuf\\' => 16,
            'Google\\LongRunning\\' => 19,
            'Google\\Auth\\' => 12,
            'Google\\ApiCore\\LongRunning\\' => 27,
            'Google\\ApiCore\\' => 15,
            'Google\\Analytics\\Data\\' => 22,
            'Google\\Analytics\\Admin\\' => 23,
            'Google\\' => 7,
            'GPBMetadata\\Google\\Protobuf\\' => 28,
            'GPBMetadata\\Google\\Longrunning\\' => 31,
            'GPBMetadata\\Google\\Analytics\\Data\\' => 34,
            'GPBMetadata\\Google\\Analytics\\Admin\\' => 35,
            'GPBMetadata\\Google\\' => 19,
            'GPBMetadata\\ApiCore\\' => 20,
        ),
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib3\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'bcmath_compat\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/bcmath_compat/src',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-factory/src',
            1 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Psr\\Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-client/src',
        ),
        'Psr\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/cache/src',
        ),
        'ParagonIE\\ConstantTime\\' => 
        array (
            0 => __DIR__ . '/..' . '/paragonie/constant_time_encoding/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'Grpc\\Gcp\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/grpc-gcp/src',
        ),
        'Grpc\\' => 
        array (
            0 => __DIR__ . '/..' . '/grpc/grpc/src/lib',
        ),
        'Google\\Service\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/apiclient-services/src',
        ),
        'Google\\Protobuf\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/protobuf/src/Google/Protobuf',
        ),
        'Google\\LongRunning\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/longrunning/src/LongRunning',
        ),
        'Google\\Auth\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/auth/src',
        ),
        'Google\\ApiCore\\LongRunning\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/longrunning/src/ApiCore/LongRunning',
        ),
        'Google\\ApiCore\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/gax/src',
        ),
        'Google\\Analytics\\Data\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/analytics-data/src',
        ),
        'Google\\Analytics\\Admin\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/analytics-admin/src',
        ),
        'Google\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/apiclient/src',
            1 => __DIR__ . '/..' . '/google/common-protos/src',
        ),
        'GPBMetadata\\Google\\Protobuf\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/protobuf/src/GPBMetadata/Google/Protobuf',
        ),
        'GPBMetadata\\Google\\Longrunning\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/longrunning/metadata/Longrunning',
        ),
        'GPBMetadata\\Google\\Analytics\\Data\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/analytics-data/metadata',
        ),
        'GPBMetadata\\Google\\Analytics\\Admin\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/analytics-admin/metadata',
        ),
        'GPBMetadata\\Google\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/common-protos/metadata',
        ),
        'GPBMetadata\\ApiCore\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/gax/metadata/ApiCore',
        ),
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc47ec56ef29ebf649dc1fc01ed66ce2d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc47ec56ef29ebf649dc1fc01ed66ce2d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc47ec56ef29ebf649dc1fc01ed66ce2d::$classMap;

        }, null, ClassLoader::class);
    }
}
