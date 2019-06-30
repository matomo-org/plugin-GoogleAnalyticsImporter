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
use Piwik\Cache\Transient;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Plugins\DevicesDetection\Archiver;
use Piwik\Plugins\GoogleAnalyticsImporter\GoogleAnalyticsQueryService;
use Psr\Log\LoggerInterface;
use DeviceDetector\Parser\Device\DeviceParserAbstract AS DeviceParser;

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

    /**
     * @var Transient
     */
    private $cache;

    public function __construct(GoogleAnalyticsQueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);

        $this->cache = StaticContainer::get(Transient::class);

        $this->deviceBrandMap = $this->getDeviceBrandMap();
        $this->operatingSystemMap = $this->getOperatingSystemMap();
        $this->browserMap = $this->getBrowserMap();
    }

    public function importRecords(Date $day)
    {
        $this->buildDeviceTypeRecord($day);
        $this->buildDeviceBrandsRecord($day);
        $this->buildDeviceModelsRecord($day);
        $this->buildDeviceOsRecords($day);
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

    private function buildDeviceOsRecords(Date $day)
    {
        $this->buildRecord($day, 'ga:operatingSystem', Archiver::OS_RECORD_NAME, [$this, 'mapOs']);
        $this->buildRecord($day, 'ga:operatingSystemVersion', Archiver::OS_VERSION_RECORD_NAME, [$this, 'mapOsVersion']);

        $gaQuery = $this->getGaQuery();
        $table = $gaQuery->query($day, $dimensions = ['ga:operatingSystem', 'ga:operatingSystemVersion'], $this->getConversionAwareVisitMetrics());

        $operatingSystems = new DataTable();
        $operatingSystemVersions = new DataTable();

        foreach ($table->getRows() as $row) {
            $os = $this->mapOs($row->getMetadata('ga:operatingSystem'));
            $osVersion = $this->mapOsVersion($row->getMetadata('ga:operatingSystemVersion'));

            if (empty($os)) {
                $os = 'xx';
            }

            $this->addRowToTable($operatingSystems, $row, $os);
            $this->addRowToTable($operatingSystemVersions, $row, $os . ';' . $osVersion);
        }

        Common::destroy($table);

        $blob = $operatingSystems->getSerialized($this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        $this->insertBlobRecord(Archiver::OS_RECORD_NAME, $blob);
        Common::destroy($operatingSystems);

        $blob = $operatingSystemVersions->getSerialized($this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        $this->insertBlobRecord(Archiver::OS_VERSION_RECORD_NAME, $blob);
        Common::destroy($operatingSystemVersions);
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

    protected function mapCategory($category)
    {
        switch ($category) {
            case 'desktop':
                return DeviceParser::DEVICE_TYPE_DESKTOP;
            case 'tablet':
                return DeviceParser::DEVICE_TYPE_TABLET;
            case 'mobile':
                return DeviceParser::DEVICE_TYPE_SMARTPHONE;
            default:
                $this->getLogger()->warning("Unknown device category found in google analytics: $category");
                return 'xx';
        }
    }

    protected function mapBrand($brand)
    {
        return $this->getValueFromValueMapping($this->deviceBrandMap, $brand, 'device brand');
    }

    protected function mapModel($model)
    {
        return $model;
    }

    protected function mapOs($os)
    {
        return $this->getValueFromValueMapping($this->operatingSystemMap, $os, 'operating system');
    }

    protected function mapBrowser($browser)
    {
        if (is_numeric($browser)) {
            return 'xx'; // sometimes GA returns a numeric value for the browser (and no browser version). not sure why.
        }

        return $this->getValueFromValueMapping($this->browserMap, $browser, 'browser');
    }

    protected function mapOsVersion($osVersion)
    {
        return $osVersion;
    }

    protected function mapBrowserVersion($browserVersion)
    {
        return $browserVersion;
    }

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

    private function getDeviceBrandMap()
    {
        $cacheKey = 'GoogleAnalyticsImporter.DevicesDetection.deviceBrandMap';

        $result = $this->cache->fetch($cacheKey);
        if (empty($result)) {
            $result = $this->buildValueMapping(DeviceParserAbstract::$deviceBrands);
            $result['oukitel'] = $result['ouki'];
            $this->cache->save($cacheKey, $result);
        }
        return $result;
    }

    private function getOperatingSystemMap()
    {
        $cacheKey = 'GoogleAnalyticsImporter.DevicesDetection.operatingSystemMap';

        $operatingSystems = OperatingSystem::getAvailableOperatingSystems();
        $result = $this->cache->fetch($cacheKey);
        if (empty($result)) {
            $result = $this->buildValueMapping($operatingSystems);
            $result['linux'] = $result['gnu/linux'];
            $result['macintosh'] = $result['mac'];
            $this->cache->save($cacheKey, $result);
        }
        return $result;
    }

    private function getBrowserMap()
    {
        $cacheKey = 'GoogleAnalyticsImporter.DevicesDetection.browserMap';

        $availableBrowsers = Browser::getAvailableBrowsers();
        $result = $this->cache->fetch($cacheKey);
        if (empty($result)) {
            $result = $this->buildValueMapping($availableBrowsers);
            $result['edge'] = $result['microsoft edge'];
            $result['safari (in-app)'] = $result['mobile safari'];
            $result['samsung internet'] = $result['samsung browser'];
            $result['android webview'] = $result['android browser'];
            $this->cache->save($cacheKey, $result);
        }
        return $result;
    }
}
