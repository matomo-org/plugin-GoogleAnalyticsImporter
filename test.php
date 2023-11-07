<?php

namespace Matomo\Dependencies\GoogleAnalyticsImporter;

require_once __DIR__ . '/vendor/autoload.php';
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\DateRange;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Dimension;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\Metric;
/**
 * TODO(developer): Replace this variable with your Google Analytics 4
 *   property ID before running the sample.
 */
$property_id = '291800644';
// Using a default constructor instructs the client to use the credentials
// specified in GOOGLE_APPLICATION_CREDENTIALS environment variable.
$client = new BetaAnalyticsDataClient(['credentials' => '/home/altamash/Downloads/matomo-ga-importer-330802-b3ddf0474b63.json']);
// Make an API call.
$response = $client->runReport(['property' => 'properties/' . $property_id, 'dateRanges' => [new DateRange(['start_date' => '2020-03-31', 'end_date' => 'today'])], 'dimensions' => [new Dimension(['name' => 'city'])], 'metrics' => [new Metric(['name' => 'activeUsers'])]]);
// Print results of an API call.
print 'Report result: ' . \PHP_EOL;
foreach ($response->getRows() as $row) {
    print $row->getDimensionValues()[0]->getValue() . ' ' . $row->getMetricValues()[0]->getValue() . \PHP_EOL;
}
