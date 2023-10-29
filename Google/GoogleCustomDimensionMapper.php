<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Google;

use Piwik\Plugins\CustomDimensions\Dimension\Name;
use Piwik\Plugins\GoogleAnalyticsImporter\CannotImportCustomDimensionException;
use Piwik\Plugins\GoogleAnalyticsImporter\OutOfCustomDimensionsException;
class GoogleCustomDimensionMapper
{
    public function map(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\CustomDimension $gaCustomDimension)
    {
        $result = ['name' => $gaCustomDimension->getName(), 'extractions' => [], 'case_sensitive' => \true, 'scope' => $this->mapScope($gaCustomDimension), 'active' => (bool) $gaCustomDimension->getActive()];
        $blockedChars = Name::getBlockedCharacters();
        $result['name'] = str_replace($blockedChars, '', $result['name']);
        return $result;
    }
    public function mapScope(\Matomo\Dependencies\GoogleAnalyticsImporter\Google\Service\Analytics\CustomDimension $gaCustomDimension)
    {
        $scope = $gaCustomDimension->getScope();
        switch (strtolower($scope)) {
            case 'hit':
                return 'action';
            case 'session':
                return 'visit';
            default:
                throw new CannotImportCustomDimensionException($gaCustomDimension, 'unsupported scope, "' . $scope . '"');
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
            /** @var \Google\Service\Analytics\CustomDimension $gaCustomDimension */
            foreach ($gaCustomDimensions->getItems() as $gaCustomDimension) {
                try {
                    $mappedScope = $this->mapScope($gaCustomDimension);
                } catch (CannotImportCustomDimensionException $ex) {
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
