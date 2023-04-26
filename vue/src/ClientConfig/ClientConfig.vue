<!--
  Matomo - free/libre analytics platform
  @link https://matomo.org
  @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div>
    <ContentBlock
      :content-title="translate('GoogleAnalyticsImporter_ConfigureTheImporter')"
      v-if="hasClientConfiguration"
    >
      <form
        method="post"
        :action="forwardToAuthUrl"
        id="clientauthform"
      >
        <input type="hidden" name="auth_nonce" :value="authNonce" />
        <span v-if="isConfigured">
          <p>{{ translate('GoogleAnalyticsImporter_ImporterIsConfigured') }}</p>
          <button type="submit" class="btn">
            {{ translate('GoogleAnalyticsImporter_ReAuthorize') }}
          </button>
        </span>
        <span v-else>
          <p>{{ translate('GoogleAnalyticsImporter_ClientConfigSuccessfullyUpdated') }}</p>
          <button type="submit" class="btn">
            {{ translate('GoogleAnalyticsImporter_Authorize') }}
          </button>
        </span>
      </form>
    </ContentBlock>

    <ContentBlock
      v-if="hasClientConfiguration"
      :content-title="translate('GoogleAnalyticsImporter_RemoveClientConfiguration')"
    >
      <form
        :action="deleteClientCredentialsLink"
        method="POST"
        enctype="multipart/form-data"
        id="removeConfigForm"
      >
        <p>{{ translate('GoogleAnalyticsImporter_DeleteUploadedClientConfig') }}:</p>

        <input type="hidden" name="config_nonce" :value="configNonce" />

        <button type="submit" class="btn">{{ translate('General_Remove') }}</button>
      </form>
    </ContentBlock>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import {
  ContentBlock,
  MatomoUrl,
  translate,
} from 'CoreHome';

interface ClientConfigState {
  clientFileToSet: unknown;
  clientConfigTextToSet: string;
}

export default defineComponent({
  props: {
    hasClientConfiguration: Boolean,
    isConfigured: Boolean,
    authNonce: String,
    configNonce: String,
  },
  components: {
    ContentBlock,
  },
  data(): ClientConfigState {
    return {
      clientFileToSet: null,
      clientConfigTextToSet: '',
    };
  },
  computed: {
    forwardToAuthUrl() {
      return `?${MatomoUrl.stringify({
        ...MatomoUrl.urlParsed.value,
        action: 'forwardToAuth',
      })}`;
    },
    configureClientLink() {
      return `?${MatomoUrl.stringify({
        ...MatomoUrl.urlParsed.value,
        action: 'configureClient',
      })}`;
    },
    configureClientDesc2() {
      const link = 'https://matomo.org/docs/google-analytics-importer/';
      return translate(
        'GoogleAnalyticsImporter_ConfigureClientDesc2',
        `<a href="${link}" target="_blank" rel="noopener noreferrer">`,
        '</a>',
      );
    },
    deleteClientCredentialsLink() {
      return `?${MatomoUrl.stringify({
        ...MatomoUrl.urlParsed.value,
        action: 'deleteClientCredentials',
      })}`;
    },
  },
});
</script>
