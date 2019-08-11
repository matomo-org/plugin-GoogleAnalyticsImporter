/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
(function () {
    angular.module('piwikApp').run(addWidgetModificationEvents);

    addWidgetModificationEvents.$inject = ['$rootScope', 'piwikPeriods', '$location'];

    function addWidgetModificationEvents($rootScope, piwikPeriods, $location) {
        $rootScope.$on('widget:loaded', onWidgetLoaded);

        var UNSUPPORTED_REPORT_WIDGETS = [
            "Transitions.getTransitions",
            "UsersFlow.getUsersFlow",
            "UsersFlow.getUsersFlowPretty",
            "UsersFlow.getInteractionActions"
        ];

        function onWidgetLoaded(event, data) {
            var method = data.parameters.module + "." + data.parameters.action;
            if (UNSUPPORTED_REPORT_WIDGETS.indexOf(method) === -1) {
                return;
            }

            if (!piwik.importedFromGoogleStartDate || !piwik.importedFromGoogleEndDate) {
                return;
            }

            var importedFromGoogleStartDate = piwikPeriods.parseDate(piwik.importedFromGoogleStartDate);
            var importedFromGoogleEndDate = piwikPeriods.parseDate(piwik.importedFromGoogleEndDate);

            var period = getQueryParamValue('period');
            var date = getQueryParamValue('date');
            var currentPeriod = piwikPeriods.parse(period, date).getDateRange();

            var isInImportDateRange = !(importedFromGoogleStartDate.getTime() > currentPeriod[1].getTime() || importedFromGoogleEndDate.getTime() < currentPeriod[0].getTime());
            if (!isInImportDateRange) {
                return;
            }

            var helpText = '<br/> <div class="alert alert-info">' + _pk_translate('GoogleAnalyticsImporter_LogDataRequiredForReport') + '</div>';
            $(data.element).find('.card-content>div').append(helpText);
        }

        function getQueryParamValue(name) {
            return $location.search()[name] || broadcast.getValueFromUrl(name);
        }
    }
})();
