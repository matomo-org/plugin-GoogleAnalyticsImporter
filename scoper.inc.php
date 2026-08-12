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

/**
 * Move PHP 8 attributes onto their own line so the scoped dependencies still parse on our minimum supported PHP.
 *
 * PHP 7 reads `#[` as a comment running to the end of the line, so an attribute sitting alone on a line is inert
 * there while still applying on PHP 8 - the style Matomo core itself uses. Written inline, as vendors commonly do
 * for parameters, it instead comments out the rest of the signature and causes a parse error. Rector is deliberately
 * not allowed to downgrade attributes away, since they are worth keeping on PHP 8, so they are reformatted here.
 *
 * Tokenising rather than matching text keeps strings such as preg_match('#[abc]#', ...) from looking like attributes.
 */
$moveAttributesToOwnLine = static function (string $content): string {
    if (!defined('T_ATTRIBUTE')) {
        // Running on a PHP where attributes are already only comments, so there is nothing to move
        return $content;
    }

    $tokens = token_get_all($content);
    $count = count($tokens);
    $result = '';

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        if (!is_array($token) || $token[0] !== T_ATTRIBUTE) {
            $result .= is_array($token) ? $token[1] : $token;
            continue;
        }

        $lineStart = strrpos($result, "\n");
        preg_match('/^[ \t]*/', $lineStart === false ? $result : substr($result, $lineStart + 1), $matches);
        $indent = $matches[0];

        // Copy the attribute across, counting brackets so that arrays in its arguments do not end it early
        $depth = 0;
        for (; $i < $count; $i++) {
            $inner = $tokens[$i];
            $text = is_array($inner) ? $inner[1] : $inner;
            $result .= $text;

            if ((is_array($inner) && $inner[0] === T_ATTRIBUTE) || $text === '[') {
                $depth++;
            } elseif ($text === ']') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }

        // Anything but whitespace before the next newline means the attribute is inline and has to be split
        $isInline = false;
        for ($j = $i + 1; $j < $count; $j++) {
            $text = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
            $newline = strpos($text, "\n");

            if ($newline !== false) {
                $isInline = trim(substr($text, 0, $newline)) !== '';
                break;
            }
            if (trim($text) !== '') {
                $isInline = true;
                break;
            }
        }

        if (!$isInline) {
            continue;
        }

        $result .= "\n" . $indent . '    ';

        // Drop the space that used to separate the attribute from what followed it
        if (
            isset($tokens[$i + 1]) && is_array($tokens[$i + 1])
            && $tokens[$i + 1][0] === T_WHITESPACE && strpos($tokens[$i + 1][1], "\n") === false
        ) {
            $tokens[$i + 1][1] = '';
        }
    }

    return $result;
};

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

            if (
                $filePath === __DIR__ . '/vendor/google/apiclient/src/aliases.php'
                || $filePath === __DIR__ . '/vendor/google/apiclient-services/autoload.php'
            ) {
                $content = preg_replace_callback('/([\'"])Google_/', function ($matches) {
                    return $matches[1] . 'Matomo\\\\Dependencies\\\\GoogleAnalyticsImporter\\\\Google_';
                }, $content);
            }

            if ($filePath === __DIR__ . '/vendor/google/apiclient/src/Client.php') {
                $content = str_replace(
                    [
                        'Monolog\Handler\StreamHandler',
                        'Monolog\Handler\SyslogHandler', 'Monolog\Logger'
                    ],
                    [
                        '\Piwik\Plugins\Monolog\Handler\FileHandler',
                        '\Piwik\Plugins\GoogleAnalyticsImporter\Monolog\Handler\GASystemLogHandler',
                        '\Piwik\Log\Logger'
                    ],
                    $content
                );
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
            if (
                $filePath === __DIR__ . '/vendor/phpseclib/phpseclib/phpseclib/Math/BigInteger.php' || $filePath === __DIR__ . '/vendor/phpseclib/phpseclib/phpseclib/Math/BigInteger/Engines/Engine.php'
            ) {
                $content = str_replace(
                    'phpseclib3\\\\Math\\\\BigInteger\\\\Engines\\\\',
                    "{$escapedPrefix}\\\\phpseclib3\\\\Math\\\\BigInteger\\\\Engines\\\\",
                    $content
                );
            }

            // Remove the newly added namespace for the bcmath functions
            if ($filePath === __DIR__ . '/vendor/phpseclib/bcmath_compat/lib/bcmath.php') {
                $content = str_replace("namespace {$prefix};", '', $content);
            }

            // psr7 calls \get_debug_type() and \preg_last_error_msg() unconditionally, so this polyfill has to keep
            // declaring them in the global namespace. Its `use` imports a namespace rather than a class, which is why
            // php-scoper leaves it unprefixed.
            if ($filePath === __DIR__ . '/vendor/symfony/polyfill-php80/bootstrap.php') {
                $content = str_replace(
                    ["namespace {$prefix};", 'use Symfony\\Polyfill\\Php80 as p;'],
                    ['', "use {$prefix}\\Symfony\\Polyfill\\Php80 as p;"],
                    $content
                );
            }

            return $content;
        },

        // Patcher keeping attributes parseable on the minimum supported PHP version
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences, $moveAttributesToOwnLine): string {
            if ($isRenamingReferences) {
                return $content;
            }

            return $moveAttributesToOwnLine($content);
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
