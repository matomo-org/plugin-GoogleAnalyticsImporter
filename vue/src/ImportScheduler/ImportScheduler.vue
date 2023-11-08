<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div>
    <p>{{ translate('GoogleAnalyticsImporter_ScheduleImportDescription') }}</p>
    <div name="startDate">
      <Field
        uicontrol="text"
        name="startDate"
        v-model="startDate"
        :title="translate('GoogleAnalyticsImporter_StartDate')"
        :placeholder="`${translate('GoogleAnalyticsImporter_CreationDate')} (YYYY-MM-DD)`"
        :inline-help="translate('GoogleAnalyticsImporter_StartDateHelp')"
      >
      </Field>
    </div>
    <div name="endDate">
      <Field
        uicontrol="text"
        name="endDate"
        v-model="endDate"
        :title="translate('GoogleAnalyticsImporter_EndDate')"
        :placeholder="translate('GoogleAnalyticsImporter_None')"
        :inline-help="endDateHelp"
      >
      </Field>
    </div>
    <div name="propertyId">
      <Field
        uicontrol="text"
        name="propertyId"
        v-model="propertyId"
        placeholder="eg. UA-XXXXX-X"
        :title="translate('GoogleAnalyticsImporter_PropertyId')"
        :inline-help="translate('GoogleAnalyticsImporter_PropertyIdHelp')"
      >
      </Field>
    </div>
    <div name="accountId">
      <Field
        uicontrol="text"
        name="accountId"
        placeholder="eg. 1234567"
        v-model="accountId"
        :title="translate('GoogleAnalyticsImporter_AccountId')"
        :inline-help="translate('GoogleAnalyticsImporter_AccountIdHelp')"
      >
      </Field>
    </div>
    <div name="viewId">
      <Field
        uicontrol="text"
        name="viewId"
        placeholder="eg. 1234567"
        v-model="viewId"
        :title="translate('GoogleAnalyticsImporter_ViewId')"
        :inline-help="translate('GoogleAnalyticsImporter_ViewIdHelp')"
      >
      </Field>
    </div>
    <div name="isMobileApp">
      <Field
        uicontrol="checkbox"
        name="isMobileApp"
        v-model="isMobileApp"
        :title="translate('GoogleAnalyticsImporter_IsMobileApp')"
        :inline-help="translate('GoogleAnalyticsImporter_IsMobileAppHelp')"
      >
      </Field>
    </div>
    <div name="timezone">
      <Field
        uicontrol="text"
        name="timezone"
        v-model="timezone"
        :title="translate('GoogleAnalyticsImporter_Timezone')"
        :placeholder="translate('GoogleAnalyticsImporter_Optional')"
        :inline-help="timezoneHelp"
      >
      </Field>
    </div>
    <div name="extraCustomDimensions">
      <Field
        uicontrol="multituple"
        name="extraCustomDimensions"
        v-model="extraCustomDimensions"
        :title="translate('GoogleAnalyticsImporter_ExtraCustomDimensions')"
        :inline-help="extraCustomDimensionsHelp"
        :ui-control-attributes="extraCustomDimensionsField"
      >
      </Field>
    </div>
    <div name="forceIgnoreOutOfCustomDimSlotError">
      <Field
        uicontrol="checkbox"
        name="forceIgnoreOutOfCustomDimSlotError"
        v-model="ignoreCustomDimensionSlotCheck"
        :title="translate('GoogleAnalyticsImporter_ForceCustomDimensionSlotCheck')"
        :inline-help="forceIgnoreOutOfCustomDimSlotErrorHelp"
      />
    </div>
    <h3>{{ translate('GoogleAnalyticsImporter_Troubleshooting') }}</h3>
    <div name="isVerboseLoggingEnabled">
      <Field
        uicontrol="checkbox"
        name="isVerboseLoggingEnabled"
        v-model="isVerboseLoggingEnabled"
        :title="translate('GoogleAnalyticsImporter_IsVerboseLoggingEnabled')"
        :inline-help="isVerboseLoggingEnabledHelp"
      />
    </div>
    <button
      type="submit"
      id="startImportSubmit"
      class="btn"
      @click="startImport()"
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
  extraCustomDimensions: ExtraCustomDimension[];
  isVerboseLoggingEnabled: boolean;
  ignoreCustomDimensionSlotCheck: boolean;
  startDate: string;
  endDate: string;
  propertyId: string;
  accountId: string;
  viewId: string;
  isMobileApp: boolean;
  timezone: string;
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
  },
  components: {
    Field,
  },
  data(): ImportSchedulerState {
    return {
      isStartingImport: false,
      extraCustomDimensions: [],
      isVerboseLoggingEnabled: false,
      ignoreCustomDimensionSlotCheck: false,
      startDate: '',
      endDate: '',
      propertyId: '',
      accountId: '',
      viewId: '',
      isMobileApp: false,
      timezone: '',
    };
  },
  created() {
    return this;
  },
  methods: {
    startImport() {
      if (this.startDate) {
        try {
          parseDate(this.startDate);
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
      const forceCustomDimensionSlotCheck = !this.ignoreCustomDimensionSlotCheck;
      return AjaxHelper.post(
        {
          module: 'GoogleAnalyticsImporter',
          action: 'startImport',
          startDate: this.startDate,
          endDate: this.endDate,
          propertyId: this.propertyId,
          viewId: this.viewId,
          nonce: this.startImportNonce,
          accountId: this.accountId,
          isMobileApp: this.isMobileApp ? '1' : '0',
          timezone: this.timezone,
          extraCustomDimensions: this.extraCustomDimensions as unknown as QueryParameters,
          isVerboseLoggingEnabled: this.isVerboseLoggingEnabled ? '1' : '0',
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
        'GoogleAnalyticsImporter_TimezoneHelp',
        `<a href="${url}" rel="noreferrer noopener" target="_blank">`,
        '</a>',
      );
    },
    extraCustomDimensionsHelp() {
      const link = 'https://ga-dev-tools.appspot.com/dimensions-metrics-explorer/';
      return translate(
        'GoogleAnalyticsImporter_ExtraCustomDimensionsHelp',
        `<a href="${link}" rel="noreferrer noopener" target="_blank">`,
        '</a>',
      );
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
