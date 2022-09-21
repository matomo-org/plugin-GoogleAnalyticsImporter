<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Isolated\Symfony\Component\Finder\Finder;

$dependenciesToPrefix = json_decode(getenv('MATOMO_DEPENDENCIES_TO_PREFIX'));

return [
    'prefix' => 'Matomo\Dependencies\GoogleAnalyticsImporter',
    'finders' => array_map(function ($dependency) {
        return Finder::create()
            ->files()
            ->in($dependency);
    }, $dependenciesToPrefix),
    'patchers' => [
        // define custom patchers here
    ],
    'exclude-namespaces' => [
        // namespaces to exclude from patching
    ],
];