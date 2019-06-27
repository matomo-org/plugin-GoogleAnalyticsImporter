<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Importers\DevicesDetection;

use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
use DeviceDetector\Parser\OperatingSystem;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Plugins\DevicesDetection\Archiver;
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsQueryService;
use Psr\Log\LoggerInterface;

class RecordImporter extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporter
{
    const PLUGIN_NAME = 'DevicesDetection';

    /**
     * @var array
     */
    private $deviceBrandMap;

    /**
     * @var array
     */
    private $operatingSystemMap;

    /**
     * @var array
     */
    private $browserMap;

    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);

        $this->deviceBrandMap = $this->buildValueMapping(DeviceParserAbstract::$deviceBrands);

        $operatingSystems = OperatingSystem::getAvailableOperatingSystems();
        $this->operatingSystemMap = $this->buildValueMapping($operatingSystems);
        $this->operatingSystemMap['linux'] = $this->operatingSystemMap['gnu/linux'];
        $this->operatingSystemMap['macintosh'] = $this->operatingSystemMap['mac'];

        $availableBrowsers = Browser::getAvailableBrowsers();
        $this->browserMap = $this->buildValueMapping($availableBrowsers);
        $this->browserMap['edge'] = $this->browserMap['microsoft edge'];
        $this->browserMap['safari (in-app)'] = $this->browserMap['mobile safari'];
        $this->browserMap['samsung internet'] = $this->browserMap['samsung browser'];
        $this->browserMap['android webview'] = $this->browserMap['android browser'];
    }

    public function queryGoogleAnalyticsApi(Date $day)
    {
        $this->buildDeviceTypeRecord($day);
        $this->buildDeviceBrandsRecord($day);
        $this->buildDeviceModelsRecord($day);
        $this->buildDeviceOsRecord($day);
        $this->buildDeviceOsVersionsRecord($day);
        $this->buildBrowserRecords($day);
    }

    private function buildDeviceTypeRecord(Date $day)
    {
        $this->buildRecord($day, 'ga:deviceCategory', Archiver::DEVICE_TYPE_RECORD_NAME, [$this, 'mapCategory']);
    }

    private function buildDeviceBrandsRecord(Date $day)
    {
        $this->buildRecord($day, 'ga:mobileDeviceBranding', Archiver::DEVICE_BRAND_RECORD_NAME, [$this, 'mapBrand']);
    }

    private function buildDeviceModelsRecord(Date $day)
    {
        $this->buildRecord($day, 'ga:mobileDeviceModel', Archiver::DEVICE_MODEL_RECORD_NAME, [$this, 'mapModel']);
    }

    private function buildDeviceOsRecord(Date $day)
    {
        $this->buildRecord($day, 'ga:operatingSystem', Archiver::OS_RECORD_NAME, [$this, 'mapOs']);
    }

    private function buildDeviceOsVersionsRecord(Date $day)
    {
        $this->buildRecord($day, 'ga:operatingSystemVersion', Archiver::OS_VERSION_RECORD_NAME, [$this, 'mapOsVersion']);
    }

    private function buildBrowserRecords(Date $day)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:browser', 'ga:browserVersion'], $this->getConversionAwareVisitMetrics());

        $browsers = new DataTable();
        $browserVersions = new DataTable();

        foreach ($table->getRows() as $row) {
            $browser = $this->mapBrowser($row->getMetadata('ga:browser'));
            $browserVersion = $this->mapBrowserVersion($row->getMetadata('ga:browserVersion'));

            if (empty($browser)) {
                $browser = 'xx';
            }

            $this->addRowToTable($browsers, $row, $browser);
            $this->addRowToTable($browserVersions, $row, $browser . ';' . $browserVersion);
        }

        Common::destroy($table);

        $blob = $browsers->getSerialized($this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        $this->insertBlobRecord(Archiver::BROWSER_RECORD_NAME, $blob);
        Common::destroy($browsers);

        $blob = $browserVersions->getSerialized($this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        $this->insertBlobRecord(Archiver::BROWSER_VERSION_RECORD_NAME, $blob);
        Common::destroy($browserVersions);
    }

    private function buildRecord(Date $day, $dimension, $recordName, callable $mapper)
    {
        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = [$dimension], $this->getConversionAwareVisitMetrics());

        $record = new DataTable();
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            $label = $mapper($label);
            if (empty($label)) {
                $label = 'xx';
            }

            $this->addRowToTable($record, $row, $label);
        }

        Common::destroy($table);

        $blob = $record->getSerialized($this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        $this->insertBlobRecord($recordName, $blob);
        Common::destroy($record);
    }

    private function mapCategory($category)
    {
        switch ($category) {
            case 'desktop':
            case 'tablet':
                return $category;
            case 'mobile':
                return 'smartphone';
            default:
                $this->getLogger()->warning("Unknown device category found in google analytics: $category");
                return 'xx';
        }
    }

    private function mapBrand($brand)
    {
        return $this->getValueFromValueMapping($this->deviceBrandMap, $brand, 'device brand');
    }

    private function mapModel($model)
    {
        return $model;
    }

    private function mapOs($os)
    {
        return $this->getValueFromValueMapping($this->operatingSystemMap, $os, 'operating system');
    }

    private function mapBrowser($browser)
    {
        if (is_numeric($browser)) {
            return 'xx'; // sometimes GA returns a numeric value for the browser (and no browser version). not sure why.
        }

        return $this->getValueFromValueMapping($this->browserMap, $browser, 'browser');
    }

    private function mapOsVersion($osVersion)
    {
        return $osVersion;
    }

    private function mapBrowserVersion($browserVersion)
    {
        return $browserVersion;
    }

    // TODO: cache these in transient cache
    private function buildValueMapping($deviceParserValues)
    {
        $map = [];
        foreach ($deviceParserValues as $short => $long) {
            $map[strtolower($long)] = $short;
        }
        return $map;
    }

    private function getValueFromValueMapping(array $valueMapping, $value, $valueType)
    {
        $cleanValue = trim(strtolower($value));
        if (isset($valueMapping[$cleanValue])) {
            return $valueMapping[$cleanValue];
        }

        if (!empty($value)) {
            $this->getLogger()->warning("Encountered unknown $valueType: $value. A new mapping should be added.");
        }

        return $value;
    }
}
