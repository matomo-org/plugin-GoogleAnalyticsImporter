/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
(function () {
    angular.module('piwikApp').controller('ImportStatusController', ImportStatusController);

    ImportStatusController.$inject = [
        'piwikApi',
    ];

    function ImportStatusController(piwikApi) {
        var vm = this;
        vm.deleteImportStatus = deleteImportStatus;

        function deleteImportStatus(idSite, isDone) {
            // TODO: if isDone, confirm via modal
            return piwikApi.post({
                module: 'GoogleAnalyticsImporter',
                action: 'deleteImportStatus',
                idSite: idSite,
            })['finally'](function () {
                window.location.reload();
            });
        }

        return vm;
    }
})();