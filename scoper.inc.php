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

return [
    'prefix' => 'Matomo\Dependencies\GoogleAnalyticsImporter',
    'finders' => array_map(function ($dependency) {
        return Finder::create()
            ->files()
            ->in($dependency);
    }, $dependenciesToPrefix),
    'patchers' => [
        // patchers for protobuf
        static function (string $filePath, string $prefix, string $content): string {
            $klassReplaceCode = <<<EOC
\$unprefixedKlass = str_replace('Matomo\\Dependencies\\GoogleAnalyticsImporter\\\\\', '', \$klass);
        if (isset(\$this->%1\$s[\$unprefixedKlass])) {
            return \$this->%1\$s[\$unprefixedKlass];
        }
EOC;

            // remove namespace prefix when using classes in protobuf, so it matches generated binary data in the library
            if (preg_match('%google/protobuf/src/Google/Protobuf/Internal/DescriptorPool\\.php$%', $filePath)) {
                $functions = [
                    'getEnumDescriptorByClassName' => 'class_to_enum_desc',
                    'getDescriptorByClassName' => 'class_to_desc',
                ];
                foreach ($functions as $fn => $paramVal) {
                    $content = preg_replace(
                        '/(public function ' . $fn . '\(\$klass\)\s+\{\s+)/',
                        '$1' . sprintf($klassReplaceCode, $paramVal),
                        $content,
                    );
                }
            } else if (preg_match('%google/protobuf/src/Google/Protobuf/Internal/GPBUtil\\.php$%', $filePath)) {
                $content = str_replace(
                    '$var->getClass() !== $klass',
                    '$var->getClass() !== $klass && \'Matomo\Dependencies\GoogleAnalyticsImporter\\\\\' . $var->getClass() !== $klass',
                    $content,
                );

                $replaceCode = "\$klass = strpos(\$klass, 'Matomo\Dependencies\GoogleAnalyticsImporter\\\\\') === 0 ? \$klass : 'Matomo\Dependencies\GoogleAnalyticsImporter\\\\\' . \$klass;\n        ";
                $content = preg_replace(
                    '/(public static function checkMessage\(\&\$var, \$klass, \$newClass = null\)\s+\{\s+)/',
                    '$1' . $replaceCode,
                    $content,
                );
            }

            return $content;
        },
    ],
    'include-namespaces' => $namespacesToPrefix,
];