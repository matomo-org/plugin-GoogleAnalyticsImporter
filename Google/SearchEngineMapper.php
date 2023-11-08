<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\Plugins\Referrers\SearchEngine;
use Piwik\Log\LoggerInterface;
class SearchEngineMapper
{
    private $sourcesToSearchEngines = [];
    /**
     * @var LoggerInterface
     */
    private $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $searchEngines = SearchEngine::getInstance();
        foreach ($searchEngines->getDefinitions() as $definition) {
            $lowerName = strtolower($definition['name']);
            $this->sourcesToSearchEngines[$lowerName] = $definition;
            $simpleName = preg_replace('/[^a-zA-Z0-9]/', '', $lowerName);
            $this->sourcesToSearchEngines[$simpleName] = $definition;
        }
        $this->sourcesToSearchEngines['conduit'] = $this->sourcesToSearchEngines['ask'];
        $this->sourcesToSearchEngines['search-results'] = $this->sourcesToSearchEngines['ask'];
        $this->sourcesToSearchEngines['images.google'] = $this->sourcesToSearchEngines['google images'];
        $this->sourcesToSearchEngines['incredimail'] = $this->sourcesToSearchEngines['google'];
        $this->sourcesToSearchEngines['alice'] = $this->sourcesToSearchEngines['yandex'];
        $this->sourcesToSearchEngines['live'] = $this->sourcesToSearchEngines['bing'];
        $this->sourcesToSearchEngines['msn'] = $this->sourcesToSearchEngines['bing'];
        $this->sourcesToSearchEngines['search'] = $this->sourcesToSearchEngines['ask'];
        $this->sourcesToSearchEngines['ecosia.org'] = $this->sourcesToSearchEngines['ecosia'];
        $this->sourcesToSearchEngines['qwant.com'] = $this->sourcesToSearchEngines['qwant'];
        $this->sourcesToSearchEngines['avg'] = ['name' => 'xx'];
        // TODO: not detected by matomo
    }
    public function mapSourceToSearchEngine($source)
    {
        $lowerSource = strtolower($source);
        if (isset($this->sourcesToSearchEngines[$lowerSource])) {
            return $this->sourcesToSearchEngines[$lowerSource]['name'];
        }
        $simpleName = preg_replace('/[^a-zA-Z0-9]/', '', $lowerSource);
        if (isset($this->sourcesToSearchEngines[$simpleName])) {
            return $this->sourcesToSearchEngines[$simpleName]['name'];
        }
        $this->logger->warning("Encountered unknown search engine source from Google Analytics: {$source}");
        return $source;
    }
    public function mapReferralMediumToSearchEngine($medium)
    {
        $searchEngines = SearchEngine::getInstance();
        $definition = $searchEngines->getDefinitionByHost($medium);
        if (empty($definition)) {
            return null;
        }
        return $definition['name'];
    }
}
