<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div class="form-group row">
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
  <div class="form-group row">
    <div class="col s12 m6">
      <form id="configFileUploadForm" :action="actionUrl" method="POST"
            enctype="multipart/form-data">
        <input type="file" id="clientfile" name="clientfile" accept=".json"
               v-on:change="processFileChange" style="display:none"/>

        <input type="hidden" name="config_nonce" :value="configNonce" />

        <button type="button" class="btn" @click="selectConfigFile()"
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
  },
  mounted() {
    document.body.onfocus = this.checkForCancel;
  },
});
</script>
