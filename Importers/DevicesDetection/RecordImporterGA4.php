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
use DeviceDetector\Parser\OperatingSystem;
use DeviceDetector\Yaml\Spyc;
use Matomo\Cache\Transient;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Plugins\DevicesDetection\Archiver;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsGA4QueryService;
use Piwik\Log\LoggerInterface;
use DeviceDetector\Parser\Device\AbstractDeviceParser as DeviceParser;
class RecordImporterGA4 extends \Piwik\Plugins\GoogleAnalyticsImporter\RecordImporterGA4
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
     * @var array
     */
    private $browserEngineMap;
    /**
     * @var Transient
     */
    private $cache;
    public function __construct(GoogleAnalyticsGA4QueryService $gaQuery, $idSite, LoggerInterface $logger)
    {
        parent::__construct($gaQuery, $idSite, $logger);
        $this->cache = StaticContainer::get(Transient::class);
        $this->deviceBrandMap = $this->getDeviceBrandMap();
        $this->operatingSystemMap = $this->getOperatingSystemMap();
        $this->browserMap = $this->getBrowserMap();
        $this->browserEngineMap = $this->getBrowserEngineMap();
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
        $this->buildRecord($day, 'deviceCategory', Archiver::DEVICE_TYPE_RECORD_NAME, [$this, 'mapCategory']);
    }
    private function buildDeviceBrandsRecord(Date $day)
    {
        $this->buildRecord($day, 'mobileDeviceBranding', Archiver::DEVICE_BRAND_RECORD_NAME, [$this, 'mapBrand']);
    }
    private function buildDeviceModelsRecord(Date $day)
    {
        $this->buildRecord($day, 'mobileDeviceModel', Archiver::DEVICE_MODEL_RECORD_NAME, [$this, 'mapModel']);
    }
    private function buildDeviceOsRecords(Date $day)
    {
        $this->buildRecord($day, 'operatingSystem', Archiver::OS_RECORD_NAME, [$this, 'mapOs']);
        $this->buildRecord($day, 'operatingSystemVersion', Archiver::OS_VERSION_RECORD_NAME, [$this, 'mapOsVersion']);
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $dimensions = ['operatingSystem', 'operatingSystemVersion'], $this->getConversionAwareVisitMetrics());
        $operatingSystems = new DataTable();
        $operatingSystemVersions = new DataTable();
        foreach ($table->getRows() as $row) {
            $os = $this->mapOs($row->getMetadata('operatingSystem'));
            $osVersion = $this->mapOsVersion($row->getMetadata('operatingSystemVersion'));
            if (empty($os)) {
                $os = 'xx';
            }
            $this->addRowToTable($operatingSystems, $row, $os);
            $this->addRowToTable($operatingSystemVersions, $row, $os . ';' . $osVersion);
        }
        Common::destroy($table);
        $this->insertRecord(Archiver::OS_RECORD_NAME, $operatingSystems, $this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        Common::destroy($operatingSystems);
        $this->insertRecord(Archiver::OS_VERSION_RECORD_NAME, $operatingSystemVersions, $this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        Common::destroy($operatingSystemVersions);
    }
    private function buildBrowserRecords(Date $day)
    {
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $dimensions = ['browser'], $this->getConversionAwareVisitMetrics());
        $browsers = new DataTable();
        $browserVersions = new DataTable();
        $browserEngines = new DataTable();
        foreach ($table->getRows() as $row) {
            $browser = $this->mapBrowser($row->getMetadata('browser'));
            if (empty($browser)) {
                $browser = 'xx';
            }
            //            $browserVersion = $this->mapBrowserVersion($row->getMetadata('ga:browserVersion'));  Not available in GA4
            $browserEngine = $this->mapBrowserEngine($row->getMetadata('browser'));
            if (empty($browserEngine)) {
                $browserEngine = 'xx';
            }
            $this->addRowToTable($browsers, $row, $browser);
            //            $this->addRowToTable($browserVersions, $row, $browser . ';' . $browserVersion);  Not available in GA4
            $this->addRowToTable($browserEngines, $row, $browserEngine);
        }
        Common::destroy($table);
        $this->insertRecord(Archiver::BROWSER_ENGINE_RECORD_NAME, $browserEngines, $this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        Common::destroy($browserEngines);
        $this->insertRecord(Archiver::BROWSER_RECORD_NAME, $browsers, $this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        Common::destroy($browsers);
        $this->insertRecord(Archiver::BROWSER_VERSION_RECORD_NAME, $browserVersions, $this->getStandardMaximumRows(), $this->getStandardMaximumRows());
        Common::destroy($browserVersions);
    }
    private function buildRecord(Date $day, $dimension, $recordName, callable $mapper)
    {
        $gaQuery = $this->getGaClient();
        $table = $gaQuery->query($day, $dimensions = [$dimension], $this->getConversionAwareVisitMetrics());
        $record = new DataTable();
        foreach ($table->getRows() as $row) {
            $label = $row->getMetadata($dimension);
            if (empty($label)) {
                $label = parent::NOT_SET_IN_GA_LABEL;
            } else {
                $originalLabel = $label;
                $label = $mapper($label);
                if ($originalLabel === \false || $originalLabel === null || $originalLabel === '') {
                    $label = 'xx';
                }
            }
            $this->addRowToTable($record, $row, $label);
        }
        Common::destroy($table);
        $this->insertRecord($recordName, $record, $this->getStandardMaximumRows(), $this->getStandardMaximumRows());
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
                $this->getLogger()->warning("Encountered unknown device category in google analytics: {$category}");
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
            return 'xx';
            // sometimes GA returns a numeric value for the browser (and no browser version). not sure why.
        }
        return $this->getValueFromValueMapping($this->browserMap, $browser, 'browser');
    }
    private function mapBrowserEngine($browser)
    {
        return $this->getValueFromValueMapping($this->browserEngineMap, $browser, null);
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
            $lower = trim(strtolower($long));
            $map[$lower] = $short;
            $stripped = preg_replace('/[^a-zA-Z0-9]/', '', $lower);
            $map[$stripped] = $short;
        }
        return $map;
    }
    private function getValueFromValueMapping(array $valueMapping, $value, $valueType = null)
    {
        $cleanValue = trim(strtolower($value));
        if (isset($valueMapping[$cleanValue])) {
            return $valueMapping[$cleanValue];
        }
        $extraCleanValue = preg_replace('/[^a-zA-Z0-9]/', '', $cleanValue);
        if (isset($valueMapping[$extraCleanValue])) {
            return $valueMapping[$extraCleanValue];
        }
        if (!empty($value) && $valueType !== null) {
            $this->getLogger()->warning("Encountered unknown {$valueType}: {$value}. A new mapping should be added.");
        }
        return $value;
    }
    private function getDeviceBrandMap()
    {
        $cacheKey = 'GoogleAnalyticsImporter.DevicesDetection.deviceBrandMap';
        $result = $this->cache->fetch($cacheKey);
        if (empty($result)) {
            $result = $this->buildValueMapping(DeviceParser::$deviceBrands);
            $result['oukitel'] = $result['ouki'];
            $result['blackberry'] = $result['rim'];
            $result['tecno'] = $result['tecno mobile'];
            $result['sonyericsson'] = $result['sony ericsson'];
            $result['opera'] = 'xx';
            $result['mobiwire'] = 'xx';
            $result['creative'] = 'xx';
            $result['Mozilla'] = 'xx';
            $result['mozilla'] = 'xx';
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
            $result['blackberry'] = $result['blackberry os'];
            $result['symbianos'] = $result['symbian os'];
            $result['playstation 3'] = $result['playstation'];
            $result['playstation 4'] = $result['playstation'];
            $result['nokia'] = 'xx';
            $result['samsung'] = 'xx';
            $this->cache->save($cacheKey, $result);
        }
        return $result;
    }
    private function getBrowserMap()
    {
        $cacheKey = 'GoogleAnalyticsImporter.DevicesDetection.browserMap';
        $result = $this->cache->fetch($cacheKey);
        if (empty($result)) {
            $availableBrowsers = Browser::getAvailableBrowsers();
            $result = $this->buildValueMapping($availableBrowsers);
            $result['edge'] = $result['microsoft edge'];
            $result['safari (in-app)'] = $result['mobile safari'];
            $result['samsung internet'] = $result['samsung browser'];
            $result['android webview'] = $result['android browser'];
            $result['blackberry'] = $result['blackberry browser'];
            $result['android runtime'] = $result['android browser'];
            $result['amazon silk'] = $result['mobile silk'];
            $result['playstation 3'] = $result['netfront'];
            $result['playstation 4'] = $result['netfront'];
            $result['blackberry9300'] = $result['blackberry browser'];
            $result['ie with chrome frame'] = $result['internet explorer'];
            $result['nintendo browser'] = $result['netfront'];
            $result['nintendo wiiu'] = $result['netfront'];
            $result['nintendo wii'] = $result['netfront'];
            $result['yabrowser'] = $result['yandex browser'];
            $result['terra'] = 'xx';
            // TODO: not detected by devices detection
            $result['mozilla compatible agent'] = 'xx';
            // TODO: mostly bots, we could ignore these...
            $result['\'mozilla'] = 'xx';
            $result['mozilla'] = 'xx';
            $this->cache->save($cacheKey, $result);
        }
        return $result;
    }
    private function getBrowserEngineMap()
    {
        $cacheKey = 'GoogleAnalyticsImporter.DevicesDetection.browserEngineMap';
        $result = $this->cache->fetch($cacheKey);
        if (empty($result)) {
            $regexesFile = __DIR__ . '/../../../../vendor/matomo/device-detector/regexes/client/browsers.yml';
            $spyc = new Spyc();
            $regexes = $spyc->parseFile($regexesFile);
            $result = [];
            foreach ($regexes as $regexInfo) {
                if (empty($regexInfo['engine']['default'])) {
                    continue;
                }
                $long = $regexInfo['name'];
                $engine = $regexInfo['engine']['default'];
                $lower = trim(strtolower($long));
                $map[$lower] = $engine;
                $stripped = preg_replace('/[^a-zA-Z0-9]/', '', $lower);
                $result[$stripped] = $engine;
            }
        }
        return $result;
    }
}
