<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="form-group row" v-if="!isNoDataPage">
    <div class="col s12 m6">
      <p>{{ translate('GoogleAnalyticsImporter_ConfigureTheImporterLabel1') }}</p>
      <p>
        {{ translate('GoogleAnalyticsImporter_ConfigureTheImporterLabel2') }}<br />
        <span v-html="$sanitize(setupGoogleAnalyticsImportFaq)"></span>
      </p>
    </div>
    <div class="col s12 m6">
      <div class="form-help"
           v-html="$sanitize(translate(
             'GoogleAnalyticsImporter_ConfigureTheImporterHelp',
             '<strong>',
             '</strong>'))">
      </div>
    </div>
  </div>
  <li v-if="isNoDataPage" v-html="$sanitize(getAdvanceConnectStep01Text)"></li>
  <li v-if="isNoDataPage"
      v-text="translate('GoogleAnalyticsImporter_GAImportNoDataScreenStep02')">
  </li>
  <div class="form-group row">
    <div :class="getClass">
      <form id="configFileUploadForm" :action="actionUrl" method="POST"
            enctype="multipart/form-data">
        <input type="file" id="clientfile" name="clientfile" accept=".json"
               v-on:change="processFileChange" style="display:none"/>

        <input type="hidden" name="isNoDataPage" value="1" v-if="isNoDataPage" />
        <input type="hidden" name="config_nonce" :value="configNonce" />

        <button type="button" class="btn advance-upload-button" @click="selectConfigFile()"
                :disabled="isUploadButtonDisabled">
          <span v-show="!isUploadButtonDisabled">
            <span class="icon-upload"></span> {{ translate('General_Upload') }}</span>
          <span v-show="isUploadButtonDisabled">
            <span class="icon-upload"></span> {{ translate('GoogleAnalyticsImporter_Uploading') }}
          </span>
        </button>
        <span class="system-success connected-message-successful"
              v-if="isNoDataPage && hasClientConfiguration">
              <span class="icon-ok"></span>
              {{ translate('GoogleAnalyticsImporter_UploadSuccessful') }}
            </span>
      </form>
    </div>
  </div>
  <li v-if="isNoDataPage" v-html="$sanitize(getAdvanceConnectStep03Text)"></li>
  <div style="margin-left: 1.2rem" class="complete-note-warning"
       v-if="isNoDataPage" v-html="$sanitize(getOauthCompleteWarningMessage)"></div>
  <form target="_blank" method="post" :action="authorizeUrl" v-if="isNoDataPage">
    <input type="hidden" name="auth_nonce" :value="forwardToAuthNonce" />
    <button :disabled="hasClientConfiguration === false"
            v-text="getAuthorizeText"
            type="submit" class="btn btn-forward-to-Oauth">
    </button>
    <span class="system-success connected-message-successful"
          v-if="isNoDataPage
                && hasClientConfiguration && isConfigured">
          <span class="icon-ok"></span>
          {{ translate('GoogleAnalyticsImporter_AccountsConnectedSuccessfully') }}
        </span>
  </form>
  <li v-if="isNoDataPage" v-html="$sanitize(getAdvanceConnectStep04Text)"></li>
  <li v-if="isNoDataPage"
      v-text="translate('GoogleAnalyticsImporter_GAImportNoDataScreenStep05')">
  </li>
  <li v-if="isNoDataPage"
      v-text="translate('GoogleAnalyticsImporter_GAImportNoDataScreenStep06')">
  </li>
  <li v-if="isNoDataPage" v-html="$sanitize(getAdvanceConnectStep07Text)"></li>
  <li v-if="isNoDataPage" v-html="$sanitize(getAdvanceConnectStep08Text)"></li>
  <li v-if="isNoDataPage"
      v-text="translate('GoogleAnalyticsImporter_GAImportNoDataScreenStep09')">
  </li>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import {
  translate,
} from 'CoreHome';

export default defineComponent({
  data() {
    return {
      isSelectingFile: false,
      isUploading: false,
    };
  },
  props: {
    actionUrl: {
      type: String,
      required: true,
    },
    configNonce: {
      type: String,
      required: true,
    },
    isNoDataPage: Boolean,
    hasClientConfiguration: Boolean,
    indexActionUrl: String,
    authorizeUrl: String,
    forwardToAuthNonce: String,
    isConfigured: Boolean,
  },
  methods: {
    selectConfigFile() {
      this.isSelectingFile = true;
      const fileInput = document.getElementById('clientfile');
      if (fileInput) {
        fileInput.click();
      }
    },
    processFileChange() {
      const fileInput = document.getElementById('clientfile') as HTMLInputElement;
      const configFileUploadForm = document.getElementById('configFileUploadForm') as HTMLFormElement;
      if (fileInput && fileInput.value && configFileUploadForm) {
        this.isUploading = true;
        configFileUploadForm.submit();
      }
    },
    checkForCancel() {
      // If we're not in currently selecting a file or if we're uploading, there's no point checking
      if (!this.isSelectingFile || this.isUploading) {
        return;
      }

      // Check if the file is empty and change back from selecting status
      const fileInput = document.getElementById('clientfile') as HTMLInputElement;
      if (fileInput && !fileInput.value) {
        this.isSelectingFile = false;
      }
    },
  },
  computed: {
    setupGoogleAnalyticsImportFaq() {
      const url = 'https://matomo.org/faq/general/set-up-google-analytics-import/';
      return translate(
        'GoogleAnalyticsImporter_ConfigureTheImporterLabel3',
        `<a href="${url}" rel="noreferrer noopener" target="_blank">`,
        '</a>',
      );
    },
    isUploadButtonDisabled() {
      return this.isSelectingFile || this.isUploading;
    },
    getAdvanceConnectStep01Text() {
      const faqLink = 'https://matomo.org/faq/general/set-up-google-analytics-import/';
      return this.translate(
        'GoogleAnalyticsImporter_GAImportNoDataScreenStep01',
        `<a href="${faqLink}" target="_blank" rel="noreferrer noopener">`,
        '</a>',
      );
    },
    getAdvanceConnectStep03Text() {
      return this.translate(
        'GoogleAnalyticsImporter_GAImportNoDataScreenStep03',
        this.translate('GoogleAnalyticsImporter_Authorize'),
      );
    },
    getAdvanceConnectStep04Text() {
      const faqLink = 'https://matomo.org/faq/general/running-the-google-analytics-import/';
      return this.translate(
        'GoogleAnalyticsImporter_GAImportNoDataScreenStep04',
        `<a href="${this.indexActionUrl}" target="_blank" rel="noreferrer noopener">`,
        '</a>',
        `<a href="${faqLink}" target="_blank" rel="noreferrer noopener">`,
        '</a>',
      );
    },
    getAdvanceConnectStep05Text() {
      return this.translate(
        'GoogleAnalyticsImporter_GAImportNoDataScreenStep05',
        `<a href="${this.indexActionUrl}" target="_blank" rel="noreferrer noopener">`,
        '</a>',
      );
    },
    getAdvanceConnectStep07Text() {
      return `${this.translate(
        'GoogleAnalyticsImporter_GAImportNoDataScreenStep07',
        this.translate('GoogleAnalyticsImporter_Start'),
      )}<br><div style="margin-left: 1.2rem">${this.translate('GoogleAnalyticsImporter_GAImportNoDataScreenStep07Note', '<strong>', '</strong>', this.translate('GoogleAnalyticsImporter_Start'))}</div>`;
    },
    getAdvanceConnectStep08Text() {
      return this.translate(
        'GoogleAnalyticsImporter_GAImportNoDataScreenStep08',
        `<a href="${this.indexActionUrl}" target="_blank" rel="noreferrer noopener">`,
        '</a>',
      );
    },
    getOauthCompleteWarningMessage() {
      return this.translate(
        'GoogleAnalyticsImporter_GoogleOauthCompleteWarning',
        '<strong>',
        '</strong>',
      );
    },
    getClass() {
      let classes = 'col s12';
      if (this.isNoDataPage) {
        classes += ' p-half-point';
      } else {
        classes += ' m6';
      }
      return classes;
    },
    getAuthorizeText() {
      if (this.isConfigured) {
        return this.translate('GoogleAnalyticsImporter_ReAuthorize');
      }
      return this.translate('GoogleAnalyticsImporter_Authorize');
    },
  },
  mounted() {
    document.body.onfocus = this.checkForCancel;
  },
});
</script>
