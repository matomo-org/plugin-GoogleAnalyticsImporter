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
  <li v-if="isNoDataPage" v-html="getAdvanceConnectStep01Text"></li>
  <li v-if="isNoDataPage"
      v-text="$sanitize(
              translate('GoogleAnalyticsImporter_GAImportNoDataScreenStep02')
              )">
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
      </form>
    </div>
  </div>
  <li v-if="isNoDataPage" v-html="getAdvanceConnectStep03Text"></li>
  <div style="margin-left: 1.2rem" class="complete-note-warning"
       v-if="isNoDataPage" v-html="getOauthCompleteWarningMessage"></div>
  <form target="_blank" method="post" :action="authorizeUrl" v-if="isNoDataPage">
    <input type="hidden" name="auth_nonce" :value="forwardToAuthNonce" />
    <button :disabled="hasClientConfiguration === false"
            type="submit" class="btn btn-forward-to-Oauth">
      {{ translate('GoogleAnalyticsImporter_Authorize') }}
    </button>
  </form>
  <li v-if="isNoDataPage" v-html="getAdvanceConnectStep04Text"></li>
  <li v-if="isNoDataPage" v-html="getAdvanceConnectStep05Text"></li>
  <li v-if="isNoDataPage"
      v-text="$sanitize(translate('GoogleAnalyticsImporter_GAImportNoDataScreenStep06'))">
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
      return `${this.translate(
        'GoogleAnalyticsImporter_GAImportNoDataScreenStep04',
        `<a href="${faqLink}" target="_blank" rel="noreferrer noopener">`,
        '</a>',
      )}<br><div style="margin-left: 1.2rem">${this.translate('GoogleAnalyticsImporter_GAImportNoDataScreenStep04Note', '<strong>', '</strong>', this.translate('GoogleAnalyticsImporter_Start'))}</div>`;
    },
    getAdvanceConnectStep05Text() {
      return this.translate(
        'GoogleAnalyticsImporter_GAImportNoDataScreenStep05',
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
  },
  mounted() {
    document.body.onfocus = this.checkForCancel;
  },
});
</script>
