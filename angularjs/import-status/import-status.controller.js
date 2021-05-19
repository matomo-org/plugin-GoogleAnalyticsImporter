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
        '$element'
    ];

    function ImportStatusController(piwikApi, piwik, $element) {
        var vm = this;
        vm.nonce = null;
        vm.deleteImportStatus = deleteImportStatus;
        vm.showEditImportEndDateModal = showEditImportEndDateModal;
        vm.cancelEditImportEndDateModal = cancelEditImportEndDateModal;
        vm.changeImportEndDateModal = changeImportEndDateModal;
        vm.manuallyResume = manuallyResume;
        vm.openScheduleReimportModal = openScheduleReimportModal;
        vm.scheduleReimport = scheduleReimport;

        $element.tooltip({
            track: true,
            content: function() {
                var $this = $(this);
                if ($this.attr('piwik-field') === '') {
                    // do not show it for form fields
                    return '';
                }

                var title = $(this).attr('title');
                return piwikHelper.escape(title.replace(/\n/g, '<br />'));
            },
            show: {delay: 500, duration: 200},
            hide: false
        });

        var editImportEndDateIdSite = null;
        var reimportDateRangeIdSite = null;

        function showEditImportEndDateModal(idSite) {
            editImportEndDateIdSite = idSite;
            $('#editImportEndDate').modal({ dismissible: false }).modal('open');
        }

        function cancelEditImportEndDateModal() {
            editImportEndDateIdSite = null;
        }

        function changeImportEndDateModal() {
            piwikApi.withTokenInUrl();
            return piwikApi.post({
                module: 'GoogleAnalyticsImporter',
                action: 'changeImportEndDate',
                idSite: editImportEndDateIdSite,
                nonce: vm.changeImportEndDateNonce,
                endDate: vm.newImportEndDate
            })['finally'](function () {
                window.location.reload();
            });
        }

        function manuallyResume(idSite) {
            piwikApi.withTokenInUrl();
            return piwikApi.post({
                module: 'GoogleAnalyticsImporter',
                action: 'resumeImport',
                idSite: idSite,
                nonce: vm.resumeImportNonce
            })['finally'](function () {
                window.location.reload();
            });
        }

        function deleteImportStatus(idSite, isDoneOrForce) {
            if (!isDoneOrForce) {
                piwikHelper.modalConfirm('#confirmCancelJob', { yes: function () {
                    vm.deleteImportStatus(idSite, true);
                }});
                return;
            }

            piwikApi.withTokenInUrl();
            return piwikApi.post({
                module: 'GoogleAnalyticsImporter',
                action: 'deleteImportStatus',
                idSite: idSite,
                nonce: vm.nonce
            })['finally'](function () {
                window.location.reload();
            });
        }

        function openScheduleReimportModal(idSite) {
            reimportDateRangeIdSite = idSite;
            $('#openScheduleReimportModal').modal({ dismissible: false }).modal('open');
        }

        function scheduleReimport() {
            piwikApi.withTokenInUrl();
            return piwikApi.post({
                module: 'GoogleAnalyticsImporter',
                action: 'scheduleReImport',
                idSite: reimportDateRangeIdSite,
                startDate: vm.reimportStartDate,
                endDate: vm.reimportEndDate,
                nonce: vm.scheduleReImportNonce
            })['finally'](function () {
                window.location.reload();
            });
        }

        return vm;
    }
})();