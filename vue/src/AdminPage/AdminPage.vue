<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div>
    <Notification
      notification-id="ga-importer-help"
      context="info"
      type="transient"
      :noclear="true"
      :title="translate('GoogleAnalyticsImporter_SettingUp')"
    >
      {{ translate('GoogleAnalyticsImporter_ImporterHelp1') }}
      <span v-html="$sanitize(importerHelp2Text)"></span>
      <span v-html="$sanitize(importerHelp3Text)"></span>
    </Notification>

    <ClientConfig
      :has-client-configuration="hasClientConfiguration"
      :is-configured="isConfigured"
      :auth-nonce="authNonce"
      :config-nonce="configNonce"
    />

    <ContentBlock
      v-if="hasClientConfiguration && isConfigured"
      :content-title="translate('GoogleAnalyticsImporter_SelectImporter')"
    >
      <p>{{ translate('GoogleAnalyticsImporter_SelectImporterSelection') }}</p>
      <ImportSelector
        :import-options-ua="importOptionsUa"
        :import-options-ga4="importOptionsGa4"
        @update:selected-importer="selectedImporter = $event"
      >
      </ImportSelector>

      <div
        class="hide-import-main-div ga-import-main-div ua-main-div"
        v-show="selectedImporter === 'ua'"
      >
        <h3>{{ translate('GoogleAnalyticsImporter_ScheduleAnImport') }}</h3>
        <ImportScheduler
          vue-entry="GoogleAnalyticsImporter.ImportScheduler"
          :has-client-configuration="hasClientConfiguration"
          :is-configured="isConfigured"
          :start-import-nonce="startImportNonce"
          :max-end-date-desc="maxEndDateDesc"
          :extra-custom-dimensions-field="extraCustomDimensionsField"
        />
      </div>

      <div
        class="hide-import-main-div ga-import-main-div ga4-main-div"
        v-show="selectedImporter === 'ga4'"
      >
        <h3>{{ translate('GoogleAnalyticsImporter_ScheduleAnImportGA4') }}</h3>
        <ImportSchedulerGA4
          :start-import-nonce="startImportNonce"
          :max-end-date-desc="maxEndDateDesc"
          :extra-custom-dimensions-field="extraCustomDimensionsFieldGa4"
        />
      </div>
    </ContentBlock>

    <ContentBlock
      v-if="hasClientConfiguration && isConfigured"
      id="importStatusContainer"
      :content-title="translate('GoogleAnalyticsImporter_ImportJobs')"
    >
      <p v-if="!statuses?.length">
        {{ translate('GoogleAnalyticsImporter_ThereAreNoImportJobs') }}
      </p>
      <ImportStatus
        v-if="statuses?.length"
        :statuses="statuses"
        :stop-import-nonce="stopImportNonce"
        :change-import-end-date-nonce="changeImportEndDateNonce"
        :resume-import-nonce="resumeImportNonce"
        :schedule-re-import-nonce="scheduleReImportNonce"
      ></ImportStatus>
    </ContentBlock>

    <div
      v-if="hasClientConfiguration && isConfigured"
      class="ui-confirm"
      id="confirmCancelJob"
    >
      <h2>{{ translate('GoogleAnalyticsImporter_CancelJobConfirm') }}</h2>
      <input role="yes" type="button" :value="translate('General_Yes')"/>
      <input role="no" type="button" :value="translate('General_No')"/>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { Notification, ContentBlock, translate } from 'CoreHome';
import ClientConfig from '../ClientConfig/ClientConfig.vue';
import ImportScheduler from '../ImportScheduler/ImportScheduler.vue';
import ImportStatus from '../ImportStatus/ImportStatus.vue';
import ImportSelector from '../ImportScheduler/ImportSelector.vue';
import ImportSchedulerGA4 from '../ImportScheduler/ImportSchedulerGA4.vue';

interface AdminPageState {
  selectedImporter: string;
}

export default defineComponent({
  props: {
    hasClientConfiguration: Boolean,
    isConfigured: Boolean,
    authNonce: String,
    configNonce: String,
    startImportNonce: {
      type: String,
      required: true,
    },
    maxEndDateDesc: String,
    extraCustomDimensionsField: {
      type: Object,
      required: true,
    },
    extraCustomDimensionsFieldGa4: {
      type: Object,
      required: true,
    },
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
    importOptionsUa: {
      type: Object,
      required: true,
    },
    importOptionsGa4: {
      type: Object,
      required: true,
    },
  },
  components: {
    ImportSchedulerGA4,
    Notification,
    ContentBlock,
    ClientConfig,
    ImportScheduler,
    ImportStatus,
    ImportSelector,
  },
  data(): AdminPageState {
    return {
      selectedImporter: '',
    };
  },
  computed: {
    importerHelp2Text() {
      const link = 'https://matomo.org/docs/google-analytics-importer/';
      return translate(
        'GoogleAnalyticsImporter_ImporterHelp2',
        `<a href="${link}" target="_blank" rel="noreferrer noopener">`,
        '</a>',
      );
    },
    importerHelp3Text() {
      return translate(
        'GoogleAnalyticsImporter_ImporterHelp3',
        '<br><br><strong>',
        '</strong>',
      );
    },
  },
});
</script>
