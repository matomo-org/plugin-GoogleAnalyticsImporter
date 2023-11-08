<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\Plugins\GoogleAnalyticsImporter\CannotImportGoalException;
use Piwik\Plugins\SitesManager\API;
use Piwik\Log\LoggerInterface;
use Piwik\Url;
class GoogleGoalMapper
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * @param \Google\Service\Analytics\Goal $gaGoal
     * @throws CannotImportGoalException
     */
    public function map(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\Goal $gaGoal, $idSite)
    {
        $urls = array_filter(API::getInstance()->getSiteUrlsFromId($idSite));
        $result = $this->mapBasicGoalProperties($gaGoal);
        if ($gaGoal->getEventDetails()) {
            $this->mapEventGoal($result, $gaGoal);
        } else {
            if ($gaGoal->getUrlDestinationDetails()) {
                $this->mapUrlDestinationGoal($result, $gaGoal, $urls);
            } else {
                if ($gaGoal->getVisitTimeOnSiteDetails()) {
                    $this->mapVisitDurationGoal($result, $gaGoal);
                } else {
                    throw new CannotImportGoalException($gaGoal, 'unsupported goal type');
                }
            }
        }
        return $result;
    }
    private function mapEventGoal(array &$result, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\Goal $gaGoal)
    {
        $eventDetails = $gaGoal->getEventDetails();
        if (count($eventDetails->getEventConditions()) > 1) {
            throw new CannotImportGoalException($gaGoal, 'uses multiple event conditions');
        }
        $conditions = $eventDetails->getEventConditions();
        /** @var \Google\Service\Analytics\GoalEventDetailsEventConditions $condition */
        $condition = reset($conditions);
        switch (strtolower($condition->getType())) {
            case 'category':
                $result['match_attribute'] = 'event_category';
                break;
            case 'action':
                $result['match_attribute'] = 'event_action';
                break;
            case 'label':
                $result['match_attribute'] = 'event_name';
                break;
            case 'value':
                throw new CannotImportGoalException($gaGoal, 'goals based on event value are not supported in matomo');
        }
        list($patternType, $pattern) = $this->mapMatchType($gaGoal, $condition->getMatchType(), $condition->getExpression());
        $result['pattern'] = $pattern;
        // force 'contains', since GA does not include hostname in URL match
        if ($patternType == 'exact') {
            $patternType = 'contains';
        }
        $result['pattern_type'] = $patternType;
        if ($eventDetails->useEventValue) {
            $result['use_event_value_as_revenue'] = \true;
        }
    }
    private function mapUrlDestinationGoal(array &$result, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\Goal $gaGoal, $siteUrls)
    {
        $urlMatchDetails = $gaGoal->getUrlDestinationDetails();
        $result['match_attribute'] = 'url';
        list($patternType, $pattern) = $this->mapMatchType($gaGoal, $urlMatchDetails->getMatchType(), $urlMatchDetails->getUrl(), $siteUrls);
        $result['pattern_type'] = $patternType;
        $result['pattern'] = $pattern;
        $result['case_sensitive'] = (bool) $urlMatchDetails->getCaseSensitive();
        if (empty($urlMatchDetails->getSteps())) {
            return;
        }
        if (!\Piwik\Plugin\Manager::getInstance()->isPluginActivated('Funnels')) {
            throw new CannotImportGoalException($gaGoal, 'multiple steps in a URL destination goal found, this is only supported in Matomo through the <a href="' .
                Url::addCampaignParametersToMatomoLink('https://plugins.matomo.org/Funnels') . '">Funnels</a> plugin');
        }
        $result['funnel'] = $this->mapFunnelSteps($gaGoal, $urlMatchDetails);
    }
    private function mapVisitDurationGoal(array &$result, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\Goal $gaGoal)
    {
        $visitDurationGoalDetails = $gaGoal->getVisitTimeOnSiteDetails();
        $result['match_attribute'] = 'visit_duration';
        $result['pattern_type'] = $this->mapComparisonType($gaGoal, $visitDurationGoalDetails->getComparisonType());
        $result['pattern'] = $visitDurationGoalDetails->getComparisonValue();
    }
    public function mapManualGoal(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\Goal $gaGoal)
    {
        $result = $this->mapBasicGoalProperties($gaGoal);
        $result['match_attribute'] = 'manually';
        $result['pattern'] = 'manually';
        $result['pattern_type'] = 'contains';
        return $result;
    }
    private function mapBasicGoalProperties(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\Goal $gaGoal)
    {
        $result = ['name' => $gaGoal->getName(), 'description' => '(imported from Google Analytics, original id = ' . $gaGoal->getId() . ')', 'match_attribute' => \false, 'pattern' => \false, 'pattern_type' => \false, 'case_sensitive' => \false, 'revenue' => \false, 'allow_multiple_conversions' => \false, 'use_event_value_as_revenue' => \false];
        $value = $gaGoal->getValue();
        if (!empty($value)) {
            $result['revenue'] = $value;
        }
        return $result;
    }
    private function mapMatchType($gaGoal, $matchType, $patternValue, $siteUrls = [])
    {
        switch (strtolower($matchType)) {
            case 'regexp':
                return ['regex', $patternValue];
            case 'head':
            case 'begins_with':
                return ['regex', '^' . preg_quote($patternValue)];
            case 'exact':
                if (!$this->urlHasSiteUrlPrefix($patternValue, $siteUrls)) {
                    if (empty($siteUrls)) {
                        $this->logger->warning("This site has no URL and there is an 'exact match' goal without the URL protocol and domain. Defaulting to 'http://example.com/', but you may want to examine the goal after it is created.");
                        $baseUrl = 'http://example.com/';
                    } else {
                        $baseUrl = $siteUrls[0];
                    }
                    if (substr($baseUrl, -1, 1) != '/' && substr($patternValue, 0, 1) != '/') {
                        $baseUrl .= '/';
                    }
                    $patternValue = $baseUrl . $patternValue;
                }
                return ['exact', $patternValue];
            default:
                throw new CannotImportGoalException($gaGoal, "unknown goal match type, '{$matchType}'");
        }
    }
    private function mapComparisonType($gaGoal, $comparisonType)
    {
        if (strtolower($comparisonType) == 'greater_than') {
            return 'greater_than';
        }
        throw new CannotImportGoalException($gaGoal, 'Unsupported comparison type \'' . $comparisonType . '\'');
    }
    private function mapFunnelSteps(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\Goal $gaGoal, \Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\GoalUrlDestinationDetails $urlMatchDetails)
    {
        $steps = [];
        /** @var \Google\Service\Analytics\GoalUrlDestinationDetailsSteps $step */
        foreach ($urlMatchDetails->getSteps() as $step) {
            $steps[] = ['name' => $step->getName(), 'pattern' => $step->getUrl(), 'pattern_type' => 'path_equals', 'required' => \false];
        }
        if ($urlMatchDetails->getFirstStepRequired()) {
            $steps[0]['required'] = \true;
        }
        return $steps;
    }
    public function getGoalIdFromDescription($goal)
    {
        if (!preg_match('/id = ([^)]+)\\)/', $goal['description'], $matches)) {
            return null;
        }
        $matches[1] = trim($matches[1]);
        if (empty($matches[1])) {
            return null;
        }
        $this->logger->debug('Found goal "{goalName}" to be mapped to GA goal with ID = {gaGoalId}.', ['goalName' => $goal['name'], 'gaGoalId' => $matches[1]]);
        return $matches[1];
    }
    private function urlHasSiteUrlPrefix($patternValue, $siteUrls)
    {
        foreach ($siteUrls as $siteUrl) {
            if (strpos($patternValue, $siteUrl) === 0) {
                return \true;
            }
        }
        return \false;
    }
}
