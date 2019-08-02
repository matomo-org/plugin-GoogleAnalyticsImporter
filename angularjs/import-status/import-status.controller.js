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
        'piwik',
    ];

    function ImportStatusController(piwikApi, piwik) {
        var vm = this;
        vm.nonce = null;
        vm.deleteImportStatus = deleteImportStatus;

        function deleteImportStatus(idSite, isDoneOrForce) {
            if (!isDoneOrForce) {
                piwikHelper.modalConfirm('#confirmCancelJob', { yes: function () {
                    vm.deleteImportStatus(idSite, true);
                }});
                return;
            }

            return piwikApi.post({
                module: 'GoogleAnalyticsImporter',
                action: 'deleteImportStatus',
                idSite: idSite,
                token_auth: piwik.token_auth,
                nonce: vm.nonce
            })['finally'](function () {
                window.location.reload();
            });
        }

        return vm;
    }
})();