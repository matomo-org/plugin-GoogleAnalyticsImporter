<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\Plugins\CustomDimensions\Dimension\Name;
use Piwik\Plugins\GoogleAnalyticsImporter\CannotImportCustomDimensionGA4Exception;
use Piwik\Plugins\GoogleAnalyticsImporter\OutOfCustomDimensionsException;
class GoogleGA4CustomDimensionMapper
{
    public function map(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\CustomDimension $gaCustomDimension)
    {
        $result = ['name' => $gaCustomDimension->getDisplayName(), 'extractions' => [], 'case_sensitive' => \true, 'scope' => $this->mapScope($gaCustomDimension), 'active' => \true];
        $blockedChars = Name::getBlockedCharacters();
        $result['name'] = str_replace($blockedChars, '', $result['name']);
        return $result;
    }
    public function mapScope(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\CustomDimension $gaCustomDimension)
    {
        $scope = $gaCustomDimension->getScope();
        switch ($scope) {
            case '1':
                //Scope:Event
                return 'action';
            case '2':
                //Scope:User
                return 'visit';
            default:
                throw new CannotImportCustomDimensionGA4Exception($gaCustomDimension, 'unsupported scope, "' . $scope . '"');
        }
    }
    public function checkCustomDimensionCount($availableScopes, $gaCustomDimensions, $extraDimensions)
    {
        foreach ($availableScopes as $scope) {
            $requestedScopes = 0;
            foreach ($extraDimensions as $extraDimension) {
                if ($extraDimension['dimensionScope'] == $scope['value']) {
                    ++$requestedScopes;
                }
            }
            /** @var \Google\Analytics\Admin\V1alpha\CustomDimension $gaCustomDimension */
            foreach ($gaCustomDimensions->iterateAllElements() as $gaCustomDimension) {
                try {
                    $mappedScope = $this->mapScope($gaCustomDimension);
                } catch (CannotImportCustomDimensionGA4Exception $ex) {
                    continue;
                }
                if ($mappedScope == $scope['value']) {
                    ++$requestedScopes;
                }
            }
            $availableScopes = (int) $scope['numSlotsAvailable'];
            if ($requestedScopes > $availableScopes) {
                throw new OutOfCustomDimensionsException($requestedScopes, $availableScopes, $scope['value']);
            }
        }
    }
}
