/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
(function () {
    angular.module('piwikApp').controller('ImportSchedulerController', ImportSchedulerController);

    ImportSchedulerController.$inject = [
        'piwikApi',
        'piwikPeriods',
        'piwik'
    ];

    function ImportSchedulerController(piwikApi, piwikPeriods, piwik) {
        var vm = this;
        vm.nonce = null;
        vm.isStartingImport = false;
        vm.startImport = startImport;

        function startImport() {
            if (vm.startDate) {
                try {
                    piwikPeriods.parseDate(vm.startDate);
                } catch (e) {
                    var UI = require('piwik/UI');
                    var notification = new UI.Notification();
                    notification.show(_pk_translate('GoogleAnalyticsImporter_InvalidDateFormat', 'YYYY-MM-DD'), {context: 'error'});
                    return;
                }
            }

            vm.isStartingImport = true;

            piwikApi.withTokenInUrl();
            return piwikApi.post({
                module: 'GoogleAnalyticsImporter',
                action: 'startImport',
                startDate: vm.startDate,
                endDate: vm.endDate,
                propertyId: vm.propertyId,
                viewId: vm.viewId,
                nonce: vm.nonce,
                accountId: vm.accountId,
                isMobileApp: vm.isMobileApp ? '1' : '0'
            }, { token_auth: piwik.token_auth })['finally'](function () {
                window.location.reload();
            });
        }

        return vm;
    }
})();