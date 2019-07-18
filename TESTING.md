## Testing

This document outlines how to run the automated tests for the Google Analytics Importer plugin.

## System Test

At the moment there is only one test that tests the entire importing process. The test is in `./tests/System/ImportTest.php`.

Before running, the following environment variables must be set:

```
$ export PIWIK_TEST_GA_VIEW_ID=2352671
$ export GA_PROPERTY_ID=UA-95026-4
```

You must also provide credentials to the test GA site in some way. There are two ways to do this:

**Method 1:** Specify account credentials via environment variables. The following environment variables must be set:
**PIWIK_TEST_GA_ACCESS_TOKEN** **PIWIK_TEST_GA_CLIENT_CONFIG**.

**Method 2:** Configure your local test instance through the UI. The automated test will pick up these credentials and use them.

Note: the test site is the GA account for `http://matthieu.net/blog`.
