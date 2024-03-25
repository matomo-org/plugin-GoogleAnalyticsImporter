## Changelog

# 5.0.13
- Upgraded phpseclib to 3.0.36

# 5.0.12
- Fixes streamIds key not defined warning

# 5.0.11
- Fixed direct usage of Monolog dependencies

# 5.0.10
- Fixed bcmath polyfill not working due to missing scoper changes

# 5.0.9
- Added code to fix redirect error exception when executing via misc cron
- Updating dependencies for PHP 8.3 support

# 5.0.8
- Added code to hide GA Import tab for no-data screen

# 5.0.7
- Upgraded phpseclib to 3.0.34

# 5.0.6
- Scope vendored libraries to improve compatibility with other plugins
- Compatibility with PHP 8.3

# 5.0.5
- Added code to remember GA Import baner dismiss action

# 5.0.4
- Updated dependency (Guzzle)
- Updated translations

# 5.0.3
- Compatibility with Matomo 5 rc5

# 5.0.2
- Compatibility with Matomo 5 rc3

# 5.0.1
- Compatibility with Matomo 5

# 5.0.0
- Remove all use of AngularJS from the plugin.

# 4.6.11
- Ignore custom dimensions assigned the Item scope

# 4.6.10
- Google Connect button styling changes for Matomo cloud

# 4.6.9
- Updated dependencies (Guzzle)
- Added GA import tab in no data screen

# 4.6.8
- Added OAuth complete warning when configuring/authorizing GA OAuth.

# 4.6.7
- Added additional check for redirect URL

# 4.6.6
- Started using ga:adwordsCampaignID dimension instead of ga:campaignCode to import data

# 4.6.5
- Fixed SiteContentDetector usage for lower Matomo versions
- Added mapping for YaBrowser
- Added mappings for search engine

# 4.6.4
- Changes to support Matomo Oauth disable on cloud

# 4.6.3
- Fixed warnings for PHP 8.1 

# 4.6.2
- Improved check to ensure future and present dates are not processed today.

# 4.6.1
- Added the ability to show a security error
- Improved nonce check after authorization

# 4.6.0
- Updated dependencies to improve PHP 8.2 compatability
- Redesigned UI to simplify connecting to Google Analytics
- Updated language translations

# 4.5.2
- Improved check to determine nohup support exist or not

# 4.5.1
- Improved check to determine nohup support exist

# 4.5.0
- Added some brand mappings.
- Fixed status setting after rate limit and started pulling empty rows for GA4.
- Added code to display import notification if site has GA detected.
- Fixed duplication of Custom Dimensions.
- Fixes deprecation warnings for PHP 8.1

# 4.4.8
- GA4 - Removed itemRevenue and itemsPurchased metrics due to incompatibility

# 4.4.7
- Fixed mobile app import not working due to recent change in site creation.

# 4.4.6
- Fix to log the allowed API requests correctly for cloud.
- Updating error message when a use cancels auth to be more helpful.
- Try/catch block for extraCustomDimensions added to ensure import continues even after slot limit is reached.
- Started calling addSite API through processRequest method to ensure events are triggered.

# 4.4.5
- Added success notification screen after selecting GA properties

# 4.4.4
- Added new method to get count of imports scheduled.
- Started using polyfill for bcmath to work instead of asking users to install one.

# 4.4.3
- Started catching cannot process exception to not throw uncaught exception.

# 4.4.2
- Fixed regression due to string value being passed for date

# 4.4.1
- Handled exception being thrown on screen due to log level
- Notification message updated to show last import date
- Stop import process for specified time when quota is exceeded instead of retrying

# 4.4.0
- Added rate limiting for Analytics Importer for Matomo cloud customers
- Added code to skip retry for certain exceptions

# 4.3.6
- Added escaping for shell args

# 4.3.5
- Fixes for System testcases to work due to recent changes
- Adding more date format hints to form fields, #286

# 4.3.4
- Fixed code to resume import to import before last_day_imported

# 4.3.3
- Added empty label check for UserCountry Importer GA4

# 4.3.2
- Fixed redirect uri bug when passing domain in console command
- Fixed recent dates not importing all dates.

# 4.3.1
- Added missing translation key

# 4.3.0
- Added support to import GA4 data into Matomo
- Added code to import recent dates first
- Upgraded guzzleHTTP version to 4.5.0

# 4.2.0

Migrate AngularJS code to Vue.

# 4.1.11
Mention about the new idSite creation in the notification. 

# 4.1.10

Use correct instanceId in a multi account set up.

# 4.1.9

Changes:
* Upgraded google-apiclient library to v2.11 to make it compatible with PHP8.1.

# 4.1.8

Changes:
* Added changes to make it compatible with php8.

# 4.1.7

Changes:
* Report all types of error messages to end user for easier issue diagnosis.

# 4.1.6

Changes:
* Compatibility with Matomo 4.3.0.

# 4.1.5

Changes:
* Do not use log data purge check when invalidating week periods after a day is imported.

# 4.1.4

Changes:
* Catch cancelled import exceptions and do not propagate in import reports command
* trim property/view/account ID when starting an import to avoid errors on typos

# 4.1.3

Bug fixes:
* Order import statuses by site ID as integer value instead of as text value.

Other changes:
* Bump phpseclib/phpseclib from 2.0.29 to 2.0.31

# 4.1.2

Bug Fixes:
* Fix check for whether we should avoid tagmanager container creation.

Changes:
* Add some more logging for exceptions caught in the controller.

# 4.1.1

Bug Fixes:
* Check referrer URL comes from google when checking oauth nonce.
* Disable tagmanager container creation while creating new site to import into.
* Only show admin menu item for superusers.

# 4.1.0

New Features:
* Allow logging to a single file via DI config setting 'GoogleAnalyticsImporter.logToSingleFile'.

Changes:
* Remove extraneous google services from vendor via composer.

# 4.0.4

Changes:
* Do not use nohup on windows and allow users to disable nohup via DI config.

# 4.0.0

Compatibility with Matomo 4

# 1.5.6

Bug Fixes:
* Fix issue showing broken URLs when importing page URLs with hash values in them. Affected users will have to re-import affected days.

# 1.5.5

Changes:
* Report error without failing command when client is misconfigured in import-reports.
* Fail w/o thrown exception if lock is already acquired.

Bug Fixes:
* Fix issue where days could not be re-archived for imported sites due to lack of timestamp information present in dates.

# 1.5.4

Changes:
* Abort on all errors and report when it is due to a insufficient privileges exception.
* Ignore unknown metric exceptions.
* Better debug exception messages when errors occur during controller actions.

Bug Fixes:
* Handle GA API active custom dimension value of empty string properly.
* Handle invalid max end date configuration.
* Check for custom dimension slots before importing & allow ignoring extra custom dimensions

# 1.5.3

Changes:
* Fix referrers table subtable in imported reports so link is correct (only affects newly imported reports).
* Show last GA error if there was one when cannot reach GA API fails repeatedly.
* Use exponential backoff for when GA API backend fails.
* Fix forum link in error message.

# 1.5.2

Changes:
* Fixing typo in previous rate limit change.

# 1.5.1

Changes:
* Do not throw if the rate limit is reached just log a message.
* Added safety measure in case of broken internal import status.
* Default value missing for $maxEndDateDesc (fixes warning).
* Add link to the user guide to GA API config forms.

# 1.5.0

Changes:
* Allow lock ttl to be configured and use reexpire lock which waits to expire.
* Allow forced max end date to be specified through config.
* Set a fixed end date for Matomo for WordPress.

Bug Fixes:
* Undo forced input sanitization for client config.
* Fix reimport not respecting last_day_imported.
* Reduce amount of memory used.

# 1.4.1

Changes:
* Fix bug in referrers import triggered by not set values in referral path in GA. Imports experiencing the "label column not found" error are failing due to this bug. Re-importing with version 1.4.1 will avoid the issue.

# 1.4.0

Changes:
* Update google API client for PHP 7.4 support.

# 1.3.3

Changes:
* Use quotaUser to support multi-instance setups.

# 1.3.2

Bug fixes:
* Fix bug in ongoing import that could result in incomplete metrics being imported. Bug is more visible since changes in 1.3.0.

# 1.3.1

Changes:
* Allow re-importing ranges to work when a job is finished or has no more to import.
* Merge Time Started/Time Finished columns to provide more space in the UI.

Bug fix:
* Do not show resume button if status is 'started'.

# 1.3.0

Features:
* Improved support for shared hosting users with hosts that may kill long running processes. The import job is not attempted every hour
  if a system kills a job, it will restart promptly.
* Detect killed jobs and report to the user so they are not left in suspense.
* Allow re-importing ranges in the past.
* Add a protection for users of Matomo 3.13.2 that will disallow re-archiving of imported days (this can wipe the data that was imported).

# 1.2.2

Bug fixes:
* Fixing typo.
* Small style tweak.

# 1.2.1

Bug fixes:
* Handle old statuses without new property.

# 1.2.0

Features:
* Resume button to make it clearer that on an errored import the import doesn't have to be cancelled and restarted.
* Add feature to change import end date dynamically so users don't have to restart if they enter the wrong end date (or don't enter one).
* Support new VisitFrequency metrics in core if available.

Bug fixes:
* Tweaks to messages for clarity.
* Goals record importer was not applying new/returning segments.
* GA does not trim page titles, so ignore on error and hope users report issues.

# 1.1.2

Bug fixes:
* Fix variable not defined error.
* Make sure version is compatible w/ older versions of Matomo.

# 1.1.1

Bug fixes:
* Compatibility with Matomo for wordpress.
* Do not fail if an unmappable goal is found (in case user creates their own goal or edits a goal).

# 1.1.0

Features:
* Add new diagnostics to check for required functions and executables.
* Add troubleshooting option to enable debug logging so users can provide useful info in a bug report.
* Allow importing GA dimensions not natively supported in Matomo by creating new custom dimensions.
* Support importing mobile app properties (including screen views metrics as pageviews and screen reports as page title reports).

Bug fixes:
* Remove extra params when redirecting from processAuthCode action.
* Change include paths to better support wordpress installs.
* Do not try to import ecommerce items report if property does not support ecommerce.
* Ordering in GA API requests was not applied.
* Entry/exit page titles should not import unique visitors since we can't get that information reliably.
* URLs that end in the action default name cause a conflict w/ directory paths. This is not an issue anymore.
* Better process strange referrer URLs from GA.
* Allow specifying timezone manually in case GA timezone is not a valid PHP timezone.

# 1.0.6

* Better and configurable mysql ping for shared hosting.
* If invalid or missing config is found delete existing client configuration.

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
