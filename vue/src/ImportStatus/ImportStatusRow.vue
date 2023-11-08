<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->
<template>
  <tr :data-idsite="status.idSite">
    <td class="sitename">
      <a
        v-if="status.site"
        target="_blank"
        :href="siteUrl"
      >{{ siteName }}</a>
      <span
        style="text-transform:uppercase;"
        v-else
      >{{ translate('GoogleAnalyticsImporter_SiteDeleted') }}</span>
      <br />
      {{ translate('GoogleAnalyticsImporter_SiteID') }}: {{ status.idSite }}
    </td>
    <td class="ga-info" v-html="$sanitize(gaInfoPretty)"></td>
    <td class="status">
      {{ status.status }}
      <div v-if="status.status === 'rate_limited'">
        <span
          class="icon icon-help"
          :title="translate('GoogleAnalyticsImporter_RateLimitHelp')"
        />
        <br />
        <span v-if="status.days_finished_since_rate_limit">
        {{ translate(
            'GoogleAnalyticsImporter_FinishedImportingDaysWaiting',
            status.days_finished_since_rate_limit,
          ) }}
        </span>
      </div>
      <div v-if="status.status === 'cloud_rate_limited'">
        <span
          class="icon icon-help"
          :title="status.error"
        />
        <br />
        <span v-if="status.days_finished_since_rate_limit">
        {{ translate(
          'GoogleAnalyticsImporter_FinishedImportingDaysWaiting',
          status.days_finished_since_rate_limit,
        ) }}
        </span>
      </div>
      <div v-if="status.status === 'rate_limited_hourly'">
        <span
          class="icon icon-help"
          :title="translate('GoogleAnalyticsImporter_RateLimitHourlyHelp')"
        />
      </div>
      <div v-if="status.status === 'future_date_import_pending'">
        <span
          class="icon icon-help"
          :title="translate('GoogleAnalyticsImporter_FutureDateHelp', status.future_resume_date)"
        />
      </div>
      <div v-else-if="status.status === 'errored'">
        {{ translate('GoogleAnalyticsImporter_ErrorMessage') }}: {{ status.error || 'no message' }}
        <br />
        <span v-html="$sanitize(errorMessageBugReportRequest)"></span>
      </div>
      <div v-else-if="status.status === 'killed'">
        <span
          class="icon icon-help"
          :title="translate('GoogleAnalyticsImporter_KilledStatusHelp')"
        /><br>
        {{ translate('GoogleAnalyticsImporter_ErrorMessage') }}: {{ status.error || 'no message' }}
      </div>
    </td>
    <td class="last-date-imported">
      <div>
        {{ translate('GoogleAnalyticsImporter_LastDayImported') }}:
        {{ status.last_date_imported || noneText }}<br />
        {{ translate('GoogleAnalyticsImporter_LastDayArchived') }}:
        {{ status.last_day_archived || noneText }}<br />
        {{ translate('GoogleAnalyticsImporter_ImportStartDate') }}:
        {{ status.import_range_start || websiteCreationTime }} <br />
        {{ translate('GoogleAnalyticsImporter_ImportEndDate') }}:
        {{ status.import_range_end || noneText }}
        <br />
        <br />
      </div>
      <div v-if="status.status !== 'finished'">
        <a
          class="edit-import-end-link table-command-link"
          href
          @click.prevent="$emit('end-import')"
        >{{ translate('GoogleAnalyticsImporter_EditEndDate') }}</a>
      </div>
      <div>
        <a
          id="reimport-date-range"
          class="table-command-link"
          href
          @click.prevent="$emit('reimport')"
        >
          {{ translate('GoogleAnalyticsImporter_ReimportDate') }}
        </a>
      </div>
    </td>
    <td class="scheduled-reimports">
      <ul v-if="status.reimport_ranges?.length">
        <li v-for="(entry, index) in status.reimport_ranges" :key="index">
          {{ entry[0] }},{{ entry[1] }}
        </li>
      </ul>
      <span v-else>
      {{ translate('GoogleAnalyticsImporter_None') }}
      </span>
    </td>
    <td class="import-start-finish-times">
      {{ translate('GoogleAnalyticsImporter_ImportStartTime') }}:
      {{ status.import_start_time || noneText }}<br />
      {{ translate('GoogleAnalyticsImporter_LastResumeTime') }}:
      {{ status.last_job_start_time || noneText }}<br />
      <span v-if="status.status === 'finished'">
        {{ translate('GoogleAnalyticsImporter_TimeFinished') }}:
        {{ status.import_end_time || noneText }}
      </span>
      <span v-else-if="status.estimated_days_left_to_finish">
        <span v-if="thisJobShouldFinishToday">
          {{ translate('GoogleAnalyticsImporter_ThisJobShouldFinishToday') }}
        </span>
        <span v-else>
          {{ translate(
              'GoogleAnalyticsImporter_EstimatedFinishIn',
              status.estimated_days_left_to_finish,
            ) }}
        </span>
      </span>
      <span v-else-if="status.import_range_end">
        {{ translate('GoogleAnalyticsImporter_JobWillRunUntilManuallyCancelled') }}
      </span>
      <span v-else>
        {{ translate('General_Unknown') }}
      </span>
    </td>
    <td class="actions">
      <a
        class="table-action"
        :class="{'icon-delete': isDone, 'icon-close': !isDone}"
        @click.prevent="$emit('delete', { isDone })"
        :title="isDone
          ? translate('General_Remove')
          : translate('General_Cancel')"
      />
      <a
        v-if="['finished', 'ongoing', 'started'].indexOf(status.status) === -1"
        class="table-action icon-play"
        @click.prevent="$emit('manuallyResume')"
        :title="translate('GoogleAnalyticsImporter_ResumeDesc')"
      />
    </td>
  </tr>

</template>

<script lang="ts">
import { defineComponent } from 'vue';
import {
  MatomoUrl,
  translate,
  Matomo,
  externalLink,
} from 'CoreHome';

export default defineComponent({
  props: {
    status: {
      type: Object,
      required: true,
    },
  },
  emits: ['end-import', 'reimport', 'delete', 'manuallyResume'],
  computed: {
    isDone() {
      return this.status.status === 'finished';
    },
    siteUrl() {
      return `?${MatomoUrl.stringify({
        period: 'day',
        date: 'today',
        ...MatomoUrl.urlParsed.value,
        idSite: this.status.idSite,
        module: 'CoreHome',
        action: 'index',
      })}`;
    },
    gaInfoPretty() {
      return (this.status.gaInfoPretty || '').replace(/\n/g, '<br/>');
    },
    errorMessageBugReportRequest() {
      return translate(
        'GoogleAnalyticsImporter_ErrorMessageBugReportRequest',
        externalLink('https://forum.matomo.org/'),
        '</a>',
      );
    },
    thisJobShouldFinishToday() {
      return this.status.estimated_days_left_to_finish === 0
        || this.status.estimated_days_left_to_finish === '0';
    },
    siteName() {
      return Matomo.helper.htmlDecode(this.status.site?.name);
    },
    noneText() {
      return translate('GoogleAnalyticsImporter_None');
    },
    websiteCreationTime() {
      return translate('GoogleAnalyticsImporter_CreationDate');
    },
  },
});
</script>
