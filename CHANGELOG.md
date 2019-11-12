## Changelog

# 1.0.5

* Display query count even on rate limit in command output.
* Issue pointless mysql query to keep connection alive on systems that have a small wait_timeout.

# 1.0.4

* Add --skip-archiving option to allow avoiding launching of archiving command when importing.
* Default empty keyword value when importing campaign keyword report.
* Use CliPhp to determine php binary and default to just php if not found.

# 1.0.3

* Allow account ID to be specified explicitly since it can differ from the number in the UA-... property ID.
* Print debug message when account ID is deduced from property ID in CLI command.
* Use exponentially increasing wait time between rate limited requests when querying GA API.

# 1.0.2

* Add import date to error message when import fails.
* Fix bug in Actions record importer where it did not handle summary rows correctly.
* Fix untranslated text.

# 1.0.1

* Fix typo in actions record importer.

# 1.0.0-b1

* Initial release (beta).
