<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

class GoogleGA4GoalMapper
{
    public function mapManualGoal(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\ConversionEvent $gaGoal)
    {
        $result = $this->mapBasicGoalProperties($gaGoal);
        $result['match_attribute'] = 'manually';
        $result['pattern'] = 'manually';
        $result['pattern_type'] = 'contains';
        return $result;
    }
    public function mapEventGoal(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\ConversionEvent $gaGoal)
    {
        $result = $this->mapBasicGoalProperties($gaGoal);
        $result['match_attribute'] = 'event_name';
        $result['pattern'] = $gaGoal->getEventName();
        $result['pattern_type'] = 'exact';
        return $result;
    }
    private function mapBasicGoalProperties(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\ConversionEvent $gaGoal)
    {
        $result = [
            'name' => $gaGoal->getEventName(),
            'description' => '(imported from Google Analytics(GA4), original id = ' . $gaGoal->id . ')',
            'match_attribute' => \false,
            'pattern' => \false,
            'pattern_type' => \false,
            'case_sensitive' => \false,
            'revenue' => \false,
            'allow_multiple_conversions' => \false,
            'use_event_value_as_revenue' => \false
        ];
        return $result;
    }
}
