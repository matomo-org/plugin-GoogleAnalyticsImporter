# Matomo GoogleAnalyticsImporter Plugin

[![Build Status](https://github.com/matomo-org/plugin-GoogleAnalyticsImporter/actions/workflows/matomo-tests.yml/badge.svg?branch=4.x-dev)](https://github.com/matomo-org/plugin-GoogleAnalyticsImporter/actions/workflows/matomo-tests.yml)

## Description

Import your Google Analytics properties into Matomo. See [the documentation](https://matomo.org/docs/google-analytics-importer/) for more info.

## Dependencies
This plugin had its vendored dependencies scoped using [matomo scoper](https://github.com/matomo-org/matomo-scoper). This means that composer packages are prefixed so that they won't conflict with the same libraries used by other plugins.
If you need to update a dependency, you should be able to run `composer install` to populate the vendor directory and then follow the [instructions for scoping a plugin](https://github.com/matomo-org/matomo-scoper#how-to-scope-a-matomo-plugin). Since the scoper.inc.php file already exists, it will hopefully be as simple as running the scoper for this plugin. Once that's done, you'll also need to make some of the dependencies compatible with Matomo's minimum supported version of PHP.
This is done using the [Rector library](https://github.com/rectorphp/rector-downgrade-php). It's preferable that you install the composer package in a separate project and point to this project so that it doesn't get committed in this project. You should also have a config file saved containing the following:
```php
<?php

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // Matomo requires PHP >= 7.2.5, but PHP 7.3 is close enough. We don't want to downgrade further than necessary.
    $rectorConfig->sets([
        \Rector\Set\ValueObject\DowngradeLevelSetList::DOWN_TO_PHP_73
    ]);

    $rectorConfig->skip([
        \Rector\DowngradePhp80\Rector\Class_\DowngradeAttributeToAnnotationRector::class
    ]);
};
```
With all that in place, you should be able to run Rector like so: `vendor/bin/rector process {path_to_this_plugin/vendor/prefixed} --config={path_to_config_file}`

> **_NOTE:_**  For Matomo developers, there's an internal DevPluginCommands plugin with a command that handles scoping and running Rector. See the SearchEngineKeywordsPerformance plugin's README.md for more details.