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
        vm.isStartingImport = false;
        vm.startImport = startImport;

        function startImport() {
            if (vm.startDate) {
                try {
                    piwikPeriods.parseDate(vm.startDate);
                } catch (e) {
                    var UI = require('piwik/UI');
                    var notification = new UI.Notification();
                    // TODO: translate
                    notification.show('Invalid start date, must be in the format YYYY-MM-DD.', {context: 'error'});
                    return;
                }
            }

            vm.isStartingImport = true;

            piwikApi.withTokenInUrl();
            return piwikApi.post({
                module: 'GoogleAnalyticsImporter',
                action: 'startImport',
                startDate: vm.startDate,
                propertyId: vm.propertyId,
                viewId: vm.viewId,
                token_auth: piwik.token_auth
            })['finally'](function () {
                window.location.reload();
            });
        }

        return vm;
    }
})();