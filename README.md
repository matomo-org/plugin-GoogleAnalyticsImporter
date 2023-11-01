# Matomo GoogleAnalyticsImporter Plugin

[![Build Status](https://github.com/matomo-org/plugin-GoogleAnalyticsImporter/actions/workflows/matomo-tests.yml/badge.svg?branch=4.x-dev)](https://github.com/matomo-org/plugin-GoogleAnalyticsImporter/actions/workflows/matomo-tests.yml)

## Description

Import your Google Analytics properties into Matomo. See [the documentation](https://matomo.org/docs/google-analytics-importer/) for more info.

## Dependencies
This plugin had its vendored dependencies scoped using [matomo scoper](https://github.com/matomo-org/matomo-scoper). This means that composer packages are prefixed so that they won't conflict with the same libraries used by other plugins. If you need to update a dependency, you should be able to run `composer install` to populate the vendor directory and then follow the [instructions for scoping a plugin](https://github.com/matomo-org/matomo-scoper#how-to-scope-a-matomo-plugin). Since the scoper.inc.php file already exists, it will hopefully be as simple as running the scoper for this plugin.