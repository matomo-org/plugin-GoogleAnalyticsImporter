<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;


use Piwik\Container\StaticContainer;
use Piwik\Plugins\Referrers\SearchEngine;
use Psr\Log\LoggerInterface;

class SearchEngineMapper
{
    private static $sourcesToSearchEngines = [
        'google' => 'Google',
        'bing' => 'Bing',
        'yahoo' => 'Yahoo!',
        'ask' => 'Ask',
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function mapSourceToSearchEngine($source)
    {
        if (empty(self::$sourcesToSearchEngines[$source])) {
            $this->logger->warning("Unknown search engine source received from Google Analytics: $source"); // TODO: directions to create issue
            return $source;
        }

        return self::$sourcesToSearchEngines[$source];
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