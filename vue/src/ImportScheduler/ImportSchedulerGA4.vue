<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div>
    <p>{{ translate('GoogleAnalyticsImporter_ScheduleImportDescription') }}</p>
    <div name="startDateGA4">
      <Field
        uicontrol="text"
        name="startDateGA4"
        v-model="startDateGA4"
        :title="translate('GoogleAnalyticsImporter_StartDate')"
        :placeholder="`${translate('GoogleAnalyticsImporter_CreationDate')} (YYYY-MM-DD)`"
        :inline-help="translate('GoogleAnalyticsImporter_StartDateHelp')"
      >
      </Field>
    </div>
    <div name="endDateGA4">
      <Field
        uicontrol="text"
        name="endDateGA4"
        v-model="endDateGA4"
        :title="translate('GoogleAnalyticsImporter_EndDate')"
        :placeholder="translate('GoogleAnalyticsImporter_None')"
        :inline-help="endDateHelp"
      >
      </Field>
    </div>
    <div name="propertyIdGA4">
      <Field
        uicontrol="text"
        name="propertyIdGA4"
        v-model="propertyIdGA4"
        placeholder="eg. properties/{PROPERTY_ID}"
        :title="translate('GoogleAnalyticsImporter_PropertyIdGA4')"
        :inline-help="translate('GoogleAnalyticsImporter_PropertyIdGA4Help')"
      >
      </Field>
    </div>

    <div name="streamIds">
      <Field
        uicontrol="multituple"
        name="streamIds"
        v-model="streamIds"
        :title="translate('GoogleAnalyticsImporter_StreamIdFilter')"
        :inline-help="streamIdsFilterHelp"
        :ui-control-attributes="streamIdsField"
      >
      </Field>
    </div>

    <div name="isMobileAppGA4">
      <Field
        uicontrol="checkbox"
        name="isMobileAppGA4"
        v-model="isMobileAppGA4"
        :title="translate('GoogleAnalyticsImporter_IsMobileApp')"
        :inline-help="translate('GoogleAnalyticsImporter_IsMobileAppHelp')"
      >
      </Field>
    </div>
    <div name="timezoneGA4">
      <Field
        uicontrol="text"
        name="timezoneGA4"
        v-model="timezoneGA4"
        :title="translate('GoogleAnalyticsImporter_Timezone')"
        :placeholder="translate('GoogleAnalyticsImporter_Optional')"
        :inline-help="timezoneHelp"
      >
      </Field>
    </div>
    <div name="extraCustomDimensionsGA4">
      <Field
        uicontrol="multituple"
        name="extraCustomDimensionsGA4"
        v-model="extraCustomDimensionsGA4"
        :title="translate('GoogleAnalyticsImporter_ExtraCustomDimensions')"
        :inline-help="extraCustomDimensionsHelp"
        :ui-control-attributes="extraCustomDimensionsField"
      >
      </Field>
    </div>
    <div name="forceIgnoreOutOfCustomDimSlotErrorGA4">
      <Field
        uicontrol="checkbox"
        name="forceIgnoreOutOfCustomDimSlotErrorGA4"
        v-model="ignoreCustomDimensionSlotCheckGA4"
        :title="translate('GoogleAnalyticsImporter_ForceCustomDimensionSlotCheck')"
        :inline-help="forceIgnoreOutOfCustomDimSlotErrorHelp"
      />
    </div>
    <h3>{{ translate('GoogleAnalyticsImporter_Troubleshooting') }}</h3>
    <div name="isVerboseLoggingEnabledGA4">
      <Field
        uicontrol="checkbox"
        name="isVerboseLoggingEnabledGA4"
        v-model="isVerboseLoggingEnabledGA4"
        :title="translate('GoogleAnalyticsImporter_IsVerboseLoggingEnabled')"
        :inline-help="isVerboseLoggingEnabledHelp"
      />
    </div>
    <button
      type="submit"
      id="startImportSubmitGA4"
      class="btn"
      @click="startImportGA4()"
      :disabled="isStartingImport"
    >{{ translate('GoogleAnalyticsImporter_Start') }}</button>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import {
  translate,
  NotificationsStore,
  AjaxHelper,
  parseDate,
  externalLink,
} from 'CoreHome';
import { Field } from 'CorePluginsAdmin';

interface ExtraCustomDimension {
  gaDimension: string;
  dimensionScope: string;
}

interface ImportSchedulerState {
  isStartingImport: boolean;
  extraCustomDimensionsGA4: ExtraCustomDimension[];
  streamIds: [];
  isVerboseLoggingEnabledGA4: boolean;
  ignoreCustomDimensionSlotCheckGA4: boolean;
  startDateGA4: string;
  endDateGA4: string;
  propertyIdGA4: string;
  accountId: string;
  viewId: string;
  isMobileAppGA4: boolean;
  timezoneGA4: string;
}

export default defineComponent({
  props: {
    startImportNonce: {
      type: String,
      required: true,
    },
    maxEndDateDesc: String,
    extraCustomDimensionsField: {
      type: Object,
      required: true,
    },
    streamIdsField: {
      type: Object,
      required: true,
    },
  },
  components: {
    Field,
  },
  data(): ImportSchedulerState {
    return {
      isStartingImport: false,
      extraCustomDimensionsGA4: [],
      streamIds: [],
      isVerboseLoggingEnabledGA4: false,
      ignoreCustomDimensionSlotCheckGA4: false,
      startDateGA4: '',
      endDateGA4: '',
      propertyIdGA4: '',
      accountId: '',
      viewId: '',
      isMobileAppGA4: false,
      timezoneGA4: '',
    };
  },
  created() {
    return this;
  },
  methods: {
    startImportGA4() {
      if (this.startDateGA4) {
        try {
          parseDate(this.startDateGA4);
        } catch (e) {
          const instanceId = NotificationsStore.show({
            message: translate('GoogleAnalyticsImporter_InvalidDateFormat', ['YYYY-MM-DD']),
            context: 'error',
            type: 'transient',
          });
          NotificationsStore.scrollToNotification(instanceId);
          return undefined;
        }
      }

      this.isStartingImport = true;
      const forceCustomDimensionSlotCheck = !this.ignoreCustomDimensionSlotCheckGA4;
      return AjaxHelper.post(
        {
          module: 'GoogleAnalyticsImporter',
          action: 'startImportGA4',
          startDate: this.startDateGA4,
          endDate: this.endDateGA4,
          propertyId: this.propertyIdGA4,
          viewId: this.viewId,
          nonce: this.startImportNonce,
          accountId: this.accountId,
          isMobileApp: this.isMobileAppGA4 ? '1' : '0',
          timezone: this.timezoneGA4,
          extraCustomDimensions: this.extraCustomDimensionsGA4 as unknown as QueryParameters,
          streamIds: this.streamIds as unknown as QueryParameters,
          isVerboseLoggingEnabled: this.isVerboseLoggingEnabledGA4 ? '1' : '0',
          forceCustomDimensionSlotCheck: forceCustomDimensionSlotCheck ? '1' : '0',
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
    endDateHelp() {
      const endDateHelp = translate('GoogleAnalyticsImporter_EndDateHelpText');
      const maxEndDateDesc = this.maxEndDateDesc
        && translate('<br/><br/>GoogleAnalyticsImporter_MaxEndDateHelp', this.maxEndDateDesc);
      return `${endDateHelp} ${maxEndDateDesc || ''}`;
    },
    timezoneHelp() {
      const url = 'https://www.php.net/manual/en/timezones.php';
      return translate(
        'GoogleAnalyticsImporter_TimezoneGA4Help',
        `<a href="${url}" rel="noreferrer noopener" target="_blank">`,
        '</a>',
      );
    },
    extraCustomDimensionsHelp() {
      const link = 'https://ga-dev-tools.web.app/ga4/dimensions-metrics-explorer/';
      return translate(
        'GoogleAnalyticsImporter_ExtraCustomDimensionsGA4Help',
        `<a href="${link}" rel="noreferrer noopener" target="_blank">`,
        '</a>',
      );
    },
    streamIdsFilterHelp() {
      const url = 'https://matomo.org/faq/what-is-data-stream-in-google-analytics-4/';
      return translate('GoogleAnalyticsImporter_StreamIdFilterHelpText',
        `<a href="${url}" rel="noreferrer noopener" target="_blank">`,
        '</a>',
        '<br><br><b>',
        '</b>');
    },
    forceIgnoreOutOfCustomDimSlotErrorHelp() {
      return translate(
        'GoogleAnalyticsImporter_ForceCustomDimensionSlotCheckHelp',
        externalLink('https://matomo.org/docs/custom-dimensions/'),
        '</a>',
      );
    },
    isVerboseLoggingEnabledHelp() {
      return translate(
        'GoogleAnalyticsImporter_IsVerboseLoggingEnabledHelp',
        '/path/to/matomo/tmp/logs/',
        'gaimportlog.$idSite.$matomoDomain.log',
      );
    },
  },
});
</script>
