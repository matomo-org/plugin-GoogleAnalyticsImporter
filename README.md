# Matomo GoogleAnalyticsImporter Plugin

[![Build Status](https://github.com/matomo-org/plugin-GoogleAnalyticsImporter/actions/workflows/matomo-tests.yml/badge.svg?branch=4.x-dev)](https://github.com/matomo-org/plugin-GoogleAnalyticsImporter/actions/workflows/matomo-tests.yml)

## Description

Import your Google Analytics properties into Matomo. See [the documentation](https://matomo.org/docs/google-analytics-importer/) for more info.

## Encryption key

As of 5.2.0 the OAuth client configuration and access token stored by this plugin are encrypted at rest in the database. The encryption key is generated automatically the first time credentials are saved (or migrated) and is written to `config/config.ini.php`:

```ini
[GoogleAnalyticsImporter]
encryption_key = "..."
```

> **_IMPORTANT:_** This key must be backed up and restored together with the database. The stored credentials can only be decrypted with the matching `encryption_key`. If the key is lost, changed, or not restored alongside a database restore, the previously stored OAuth credentials become unrecoverable and you will need to re-upload the client configuration and re-authorize the importer. When migrating an installation to a new server, copy both the database **and** the `[GoogleAnalyticsImporter] encryption_key` value.

## Dependencies
This plugin had its vendored dependencies scoped using [matomo scoper](https://github.com/matomo-org/matomo-scoper). This means that composer packages are prefixed so that they won't conflict with the same libraries used by other plugins.
If you need to update a dependency, you should be able to run `composer install` to populate the vendor directory and then follow the [instructions for scoping a plugin](https://github.com/matomo-org/matomo-scoper#how-to-scope-a-matomo-plugin). Since the scoper.inc.php file already exists, it will hopefully be as simple as running the scoper for this plugin. Once that's done, you'll also need to make some of the dependencies compatible with Matomo's minimum supported version of PHP.
This is done using the [Rector library](https://github.com/rectorphp/rector-downgrade-php). It's preferable that you install the composer package in a separate project and point to this project so that it doesn't get committed in this project. You should also have a config file saved containing the following:
```php
<?php

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // Matomo 6 requires PHP >= 8.1. We don't want to downgrade further than necessary.
    $rectorConfig->sets([
        \Rector\Set\ValueObject\DowngradeLevelSetList::DOWN_TO_PHP_81
    ]);

    $rectorConfig->skip([
        \Rector\DowngradePhp80\Rector\Class_\DowngradeAttributeToAnnotationRector::class
    ]);
};
```
With all that in place, you should be able to run Rector like so: `vendor/bin/rector process {path_to_this_plugin/vendor/prefixed} --config={path_to_config_file}`

The downgrade level has to match this branch's minimum PHP, not Matomo 5's.

Before updating anything, note that `composer.json` pins `config.platform.php` to this branch's minimum. Composer otherwise resolves against whatever PHP you happen to be running, and a newer one silently produces a lock that will not install on the minimum — `ramsey/uuid` pulls in `brick/math`, whose recent releases require PHP 8.2. Keep that pin in place when regenerating. The `min-php-lint` workflow is the backstop, since tests only parse a file when something loads it.

> **_NOTE:_**  For Matomo developers, there's an internal DevPluginCommands plugin with a command that handles scoping and running Rector. See the SearchEngineKeywordsPerformance plugin's README.md for more details.