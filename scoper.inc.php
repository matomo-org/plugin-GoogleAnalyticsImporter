<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Isolated\Symfony\Component\Finder\Finder;

$dependenciesToPrefix = json_decode(getenv('MATOMO_DEPENDENCIES_TO_PREFIX'), true);
$namespacesToPrefix = json_decode(getenv('MATOMO_NAMESPACES_TO_PREFIX'), true);
$isRenamingReferences = getenv('MATOMO_RENAME_REFERENCES') == 1;
$pluginName = getenv('MATOMO_PLUGIN');

$namespacesToExclude = [];
$forceNoGlobalAlias = false;

if ($isRenamingReferences) {
    $finders = [
        Finder::create()
            ->files()
            ->in(__DIR__)
            ->exclude('vendor')
            ->exclude('node_modules')
            ->exclude('lang')
            ->exclude('javascripts')
            ->exclude('vue')
            ->notName('scoper.inc.php')
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && $file->isDir());
            })
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && !$file->getRealPath());
            }),
    ];
} else {
    $finders = array_map(function ($dependency) {
        return Finder::create()
            ->files()
            ->in($dependency);
    }, $dependenciesToPrefix);
}

$namespacesToIncludeRegexes = array_map(function ($n) {
    $n = rtrim($n, '\\');
    return '/^' . preg_quote($n) . '(?:\\\\|$)/';
}, $namespacesToPrefix);

return [
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'force-no-global-alias' => $forceNoGlobalAlias,
    'prefix' => 'Matomo\\Dependencies\\' . $pluginName,
    'finders' => $finders,
    'patchers' => [
        // patcher for google's protobuf related classes which need to prefix class names at runtime
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if ($isRenamingReferences) {
                return $content;
            }

            $descriptorClasses = [
                __DIR__ . '/vendor/google/protobuf/src/Google/Protobuf/Internal/Descriptor.php',
                __DIR__ . '/vendor/google/protobuf/src/Google/Protobuf/Internal/EnumDescriptor.php',
            ];
            if (in_array($filePath, $descriptorClasses)) {
                $content = preg_replace_callback('/function (set[^(]+)\(\\$klass\\)\s*\\{/', function (array $matches): string {
                    return <<<EOF
function {$matches[1]}(\$klass)
{
    if (strpos(\$klass, 'Matomo\\\\Dependencies\\\\GoogleAnalyticsImporter\\\\') !== 0) {
        \$klass = 'Matomo\\\\Dependencies\\\\GoogleAnalyticsImporter\\\\' . \$klass;
    }
EOF;
                }, $content);
            }

            return $content;
        },

        // patcher for unit test files that use serialized response strings
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if (!$isRenamingReferences) {
                return $content;
            }

            $unitTestFolder = __DIR__ . '/tests/Unit/Google/';
            if (strpos($filePath, $unitTestFolder) === 0) {
                $content = preg_replace_callback('/s:(\d+):"\x00Google\\\\\\\\([^\x00]+)/', function (array $matches): string {
                    $strSize = (int)$matches[1];
                    return 's:' . ($strSize + strlen('Matomo\\Dependencies\\GoogleAnalyticsImporter\\'))
                        . ":\\\"\x00Matomo\\Dependencies\\GoogleAnalyticsImporter\\Google\\"
                        . str_replace('\\\\', '\\', $matches[2]);
                }, $content);
            }

            return $content;
        },

        // patcher for captured responses used by tests
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if (!$isRenamingReferences) {
                return $content;
            }

            if ($filePath === __DIR__ . '/tests/resources/capturedresponses.log') {
                $prefix = 'Matomo\\\\Dependencies\\\\GoogleAnalyticsImporter\\\\';
                $content = preg_replace_callback('/([sO]):(\\d+):\\\\"Google_/', function ($matches) use ($prefix) {
                    return $matches[1] . ':' . ((int)$matches[2] + strlen($prefix) - 3) . ':\\"' . $prefix . 'Google_';
                }, $content);
            }

            if ($filePath === __DIR__ . '/tests/resources/capturedresponses-ga4.log') {
                // replace key values all at once
                $content = str_replace('"Google\\\\', '"Matomo\\\\Dependencies\\\\GoogleAnalyticsImporter\\\\Google\\\\', $content);

                // prefix values in array contents line by line
                $lines = explode("\n", $content);
                foreach ($lines as &$line) {
                    $data = json_decode($line, true);
                    if (empty($data)) {
                        continue;
                    }

                    $responseData = base64_decode($data[1]);

                    $prefix = 'Matomo\\Dependencies\\GoogleAnalyticsImporter\\';
                    $responseData = preg_replace_callback('/([sO]):(\\d+):"(\x00)?(Google|GuzzleHttp)\\\\/', function ($matches) use ($prefix) {
                        return $matches[1] . ':' . ((int)$matches[2] + strlen($prefix)) . ":\"" . $matches[3] . $prefix . $matches[4] . '\\';
                    }, $responseData);
                    $responseData = base64_encode($responseData);

                    $line = json_encode([$data[0], $responseData]);
                }
                $content = implode("\n", $lines);
            }

            return $content;
        },

        // patcher for files that class_alias new namespaced classes with old un-namespaced classes
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if ($isRenamingReferences) {
                return $content;
            }

            if ($filePath === __DIR__ . '/vendor/google/apiclient/src/aliases.php'
                || $filePath === __DIR__ . '/vendor/google/apiclient-services/autoload.php'
            ) {
                $content = preg_replace_callback('/([\'"])Google_/', function ($matches) {
                    return $matches[1] . 'Matomo\\\\Dependencies\\\\GoogleAnalyticsImporter\\\\Google_';
                }, $content);
            }

            if ($filePath === __DIR__ . '/vendor/google/apiclient/src/Client.php') {
                $content = str_replace(['Monolog\Handler\StreamHandler', 'Monolog\Handler\SyslogHandler', 'Monolog\Logger'], ['\Piwik\Plugins\Monolog\Handler\FileHandler', '\Piwik\Plugins\GoogleAnalyticsImporter\Monolog\Handler\GASystemLogHandler', '\Piwik\Log\Logger'], $content);
            }

            if ($filePath === __DIR__ . '/vendor/google/apiclient/src/aliases.php') {
                $content = preg_replace('/class Google_Task_Composer.*?}/', "if (!class_exists('Google_Task_Composer')) {\n$1\n}", $content);
            }

            if ($filePath === __DIR__ . '/vendor/google/apiclient-services/autoload.php') {
                // there is a core autoloader that will replace 'Matomo' in Matomo\Dependencies\... to Piwik\ if the
                // Matomo\... class cannot be found.
                //
                // normally this wouldn't be an issue, but in the importer we will be unserializing classes that
                // haven't been autoloaded, and some of those classes are handled by a special autoloader in one
                // of google's libraries. this autoloader is called after the renaming autoloader changes the name to
                // Piwik\Dependencies\..., so we need to be able to recognize both Matomo\ and Piwik\ there, or the
                // target php file won't be loaded properly.
                $replace = <<<EOF
\\spl_autoload_register(function (\$class) {
    \$class = preg_replace('/^Piwik\\\\\\\\Dependencies\\\\\\\\/', 'Matomo\\\\Dependencies\\\\', \$class);

EOF;

                $content = str_replace('\\spl_autoload_register(function ($class) {', $replace, $content);
            }

            return $content;
        },

        // Patcher for making sure that BcMath functions are properly scoped
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if ($isRenamingReferences) {
                return $content;
            }

            $functionNames = [
                'bcadd',
                'bccomp',
                'bcdiv',
                'bcmod',
                'bcmul',
                'bcpow',
                'bcpowmod',
                'bcscale',
                'bcsqrt',
                'bcsub',
                'getallheaders',
            ];

            if (dirname($filePath) === __DIR__ . '/vendor/google/protobuf/src/Google/Protobuf/Internal') {
                // Escape each function so that it doesn't try to use the file's namespace
                foreach ($functionNames as $functionName) {
                    $pattern = '/(?<!function )\b(' . $functionName . ')(?=\()/';
                    $content = preg_replace($pattern, '\\' . $functionName, $content);
                }
            }

            // Fix the string reference of a scoped dependency in the Math lib
            $escapedPrefix = str_replace('\\', '\\\\', $prefix);
            if ($filePath === __DIR__ . '/vendor/phpseclib/phpseclib/phpseclib/Math/BigInteger.php' || $filePath === __DIR__ . '/vendor/phpseclib/phpseclib/phpseclib/Math/BigInteger/Engines/Engine.php') {
                $content = str_replace('phpseclib3\\\\Math\\\\BigInteger\\\\Engines\\\\',
                    "{$escapedPrefix}\\\\phpseclib3\\\\Math\\\\BigInteger\\\\Engines\\\\", $content);
            }

            // Remove the newly added namespace for the bcmath functions
            if ($filePath === __DIR__ . '/vendor/phpseclib/bcmath_compat/lib/bcmath.php') {
                $content = str_replace("namespace {$prefix};", '', $content);
            }

            return $content;
        },
    ],
    'include-namespaces' => $namespacesToIncludeRegexes,
    'exclude-namespaces' => $namespacesToExclude,
    'exclude-constants' => [
        'PIWIK_TEST_MODE',
        '/^self::/', // work around php-scoper bug
    ],
    'exclude-functions' => ['Piwik_ShouldPrintBackTraceWithMessage'],
];
