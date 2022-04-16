<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div ref="root" v-tooltips="{ content: tooltipContent, delay: 500, duration: 200 }">
    <table class="entityTable importStatusesTable">
      <thead>
        <tr>
          <th>{{ translate('GoogleAnalyticsImporter_MatomoSite') }}</th>
          <th>{{ translate('GoogleAnalyticsImporter_GoogleAnalyticsInfo') }}</th>
          <th>{{ translate('GoogleAnalyticsImporter_Status') }}</th>
          <th>{{ translate('GoogleAnalyticsImporter_LatestDayProcessed') }}</th>
          <th>{{ translate('GoogleAnalyticsImporter_ScheduledReImports') }}</th>
          <th>{{ translate('GoogleAnalyticsImporter_StartFinishTimes') }}</th>
          <th>{{ translate('GoogleAnalyticsImporter_Actions') }}</th>
        </tr>
      </thead>
      <tbody>
        <ImportStatusRow
          v-for="(status, index) in statuses"
          :status="status"
          :key="index"
          @end-import="showEditImportEndDateModal(status.idSite)"
          @reimport="openScheduleReimportModal(status.idSite)"
          @delete="deleteImportStatus(status.idSite, $event.isDone)"
          @manually-resume="manuallyResume(status.idSite)"
        />
      </tbody>
    </table>
    <div
      class="modal"
      id="openScheduleReimportModal"
    >
      <div class="modal-content">
        <h3>{{ translate('GoogleAnalyticsImporter_EnterImportDateRange') }}</h3>
        <div>
          <Field
            name="re-import-start-date"
            uicontrol="text"
            v-model="reimportStartDate"
            :placeholder="`${translate('GoogleAnalyticsImporter_StartDate')} (YYYY-MM-DD)`"
          >
          </Field>
        </div>
        <div>
          <Field
            name="re-import-end-date"
            uicontrol="text"
            v-model="reimportEndDate"
            :placeholder="`${translate('GoogleAnalyticsImporter_EndDate')} (YYYY-MM-DD)`"
          >
          </Field>
        </div>
      </div>
      <div class="modal-footer">
        <a
          id="scheduleReimportSubmit"
          href
          class="modal-action modal-close btn"
          @click="scheduleReimport()"
        >{{ translate('GoogleAnalyticsImporter_Schedule') }}</a>
        <a
          href
          class="modal-action modal-close modal-no"
        >{{ translate('General_Cancel') }}</a>
      </div>
    </div>
    <div
      class="modal"
      id="editImportEndDate"
    >
      <div class="modal-content">
        <h3>{{ translate('GoogleAnalyticsImporter_EnterImportEndDate') }}</h3>
        <p><em>{{ translate('GoogleAnalyticsImporter_LeaveEmptyToRemove') }}</em></p>
        <div>
          <Field
            name="new-import-end-date"
            uicontrol="text"
            v-model="newImportEndDate"
          >
          </Field>
        </div>
      </div>
      <div class="modal-footer">
        <a
          href
          class="modal-action modal-close btn"
          @click="changeImportEndDateModal()"
        >{{ translate('GoogleAnalyticsImporter_Change') }}</a>
        <a
          href
          class="modal-action modal-close modal-no"
          @click="cancelEditImportEndDateModal()"
        >{{ translate('General_Cancel') }}</a>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import {
  AjaxHelper,
  Matomo,
  Tooltips,
} from 'CoreHome';
import { Field } from 'CorePluginsAdmin';
import ImportStatusRow from './ImportStatusRow.vue';

interface ImportStatusState {
  editImportEndDateIdSite: string|number|null;
  reimportDateRangeIdSite: string|number|null;
  reimportStartDate: string;
  reimportEndDate: string;
  newImportEndDate: string;
}

const { $ } = window;

export default defineComponent({
  props: {
    statuses: {
      type: Array,
      required: true,
    },
    stopImportNonce: {
      type: String,
      required: true,
    },
    changeImportEndDateNonce: {
      type: String,
      required: true,
    },
    resumeImportNonce: {
      type: String,
      required: true,
    },
    scheduleReImportNonce: {
      type: String,
      required: true,
    },
  },
  components: {
    Field,
    ImportStatusRow,
  },
  directives: {
    Tooltips,
  },
  data(): ImportStatusState {
    return {
      editImportEndDateIdSite: null,
      reimportDateRangeIdSite: null,
      reimportStartDate: '',
      reimportEndDate: '',
      newImportEndDate: '',
    };
  },
  methods: {
    showEditImportEndDateModal(idSite: string|number) {
      this.editImportEndDateIdSite = idSite;
      $('#editImportEndDate').modal({
        dismissible: false,
      }).modal('open');
    },
    cancelEditImportEndDateModal() {
      this.editImportEndDateIdSite = null;
    },
    manuallyResume(idSite: string|number) {
      return AjaxHelper.post(
        {
          module: 'GoogleAnalyticsImporter',
          action: 'resumeImport',
          idSite,
          nonce: this.resumeImportNonce,
        },
        {},
        {
          withTokenInUrl: true,
        },
      ).finally(() => {
        window.location.reload();
      });
    },
    deleteImportStatus(idSite: string|number, isDoneOrForce: boolean) {
      if (!isDoneOrForce) {
        Matomo.helper.modalConfirm('#confirmCancelJob', {
          yes: () => {
            this.deleteImportStatus(idSite, true);
          },
        });
        return undefined;
      }

      return AjaxHelper.post(
        {
          module: 'GoogleAnalyticsImporter',
          action: 'deleteImportStatus',
          idSite,
          nonce: this.stopImportNonce,
        },
        {},
        {
          withTokenInUrl: true,
        },
      ).finally(() => {
        window.location.reload();
      });
    },
    openScheduleReimportModal(idSite: string|number) {
      this.reimportDateRangeIdSite = idSite;
      $('#openScheduleReimportModal').modal({
        dismissible: false,
      }).modal('open');
    },
    changeImportEndDateModal() {
      return AjaxHelper.post(
        {
          module: 'GoogleAnalyticsImporter',
          action: 'changeImportEndDate',
          idSite: this.editImportEndDateIdSite,
          nonce: this.changeImportEndDateNonce,
          endDate: this.newImportEndDate,
        },
        {},
        {
          withTokenInUrl: true,
        },
      ).finally(() => {
        window.location.reload();
      });
    },
    scheduleReimport() {
      return AjaxHelper.post(
        {
          module: 'GoogleAnalyticsImporter',
          action: 'scheduleReImport',
          idSite: this.reimportDateRangeIdSite,
          startDate: this.reimportStartDate,
          endDate: this.reimportEndDate,
          nonce: this.scheduleReImportNonce,
        },
        {},
        {
          withTokenInUrl: true,
        },
      ).finally(() => {
        window.location.reload();
      });
    },
  },
  computed: {
    tooltipContent() {
      return function tooltipContent(this: HTMLElement) {
        const $this = $(this);

        if ($this.attr('piwik-field') === '') {
          // do not show it for form fields
          return '';
        }

        const title = $(this).attr('title') || '';
        return window.vueSanitize(title.replace(/\n/g, '<br />'));
      };
    },
  },
});
</script>
