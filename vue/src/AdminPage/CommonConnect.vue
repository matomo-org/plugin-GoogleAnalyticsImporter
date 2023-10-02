<template>
  <template v-for="(refComponent, index) in componentExtensions" :key="index">
    <ContentBlock
      v-if="!isNoDataPage"
      :content-title="isNoDataPage ? '' : translate('GoogleAnalyticsImporter_AdminMenuTitle')"
    >
      <component
        :is="refComponent"
        :manual-config-nonce="configConnectProps.manualConfigNonce"
        :base-domain="configConnectProps.baseDomain"
        :base-url="configConnectProps.baseUrl"
        :manual-action-url="configConnectProps.manualActionUrl"
        :primary-text="configConnectProps.primaryText"
        :radio-options="configConnectProps.radioOptions"
        :manual-config-text="configConnectProps.manualConfigText"
        :connect-accounts-url="configConnectProps.connectAccountsUrl"
        :connect-accounts-btn-text="configConnectProps.connectAccountsBtnText"
        :auth-url="configConnectProps.authUrl"
        :unlink-url="configConnectProps.unlinkUrl"
        :strategy="configConnectProps.strategy"
        :connected-with="configConnectProps.connectedWith"
        :is-no-data-page="isNoDataPage"
        :has-client-configuration="hasClientConfiguration"
        :index-action-url="indexActionUrl"
        :authorize-url="authorizeUrl"
        :forward-to-auth-nonce="forwardToAuthNonce"
        :is-configured="isConfigured"
        :strategy-to-use="strategyToUse"
        :additional-help-text="configConnectProps.additionalHelpText"/>
    </ContentBlock>
    <template v-else>
      <component
        :is="refComponent"
        :manual-config-nonce="configConnectProps.manualConfigNonce"
        :base-domain="configConnectProps.baseDomain"
        :base-url="configConnectProps.baseUrl"
        :manual-action-url="configConnectProps.manualActionUrl"
        :primary-text="configConnectProps.primaryText"
        :radio-options="configConnectProps.radioOptions"
        :manual-config-text="configConnectProps.manualConfigText"
        :connect-accounts-url="configConnectProps.connectAccountsUrl"
        :connect-accounts-btn-text="configConnectProps.connectAccountsBtnText"
        :auth-url="configConnectProps.authUrl"
        :unlink-url="configConnectProps.unlinkUrl"
        :strategy="configConnectProps.strategy"
        :connected-with="configConnectProps.connectedWith"
        :is-no-data-page="isNoDataPage"
        :has-client-configuration="hasClientConfiguration"
        :index-action-url="indexActionUrl"
        :authorize-url="authorizeUrl"
        :forward-to-auth-nonce="forwardToAuthNonce"
        :is-configured="isConfigured"
        :strategy-to-use="strategyToUse"
        :additional-help-text="configConnectProps.additionalHelpText"/>
    </template>
  </template>
</template>
<script lang="ts">
import {
  defineComponent, markRaw,
} from 'vue';
import {
  Notification,
  ContentBlock, useExternalPluginComponent,
} from 'CoreHome';

interface ComponentExtension {
  plugin: string;
  component: string;
}
interface ConfigureConnectionRadioOption {
  connectAccounts: string;
  manual: string;
}
interface ConfigureConnectionProps {
  baseDomain: string;
  baseUrl: string;
  manualConfigNonce: string;
  manualActionUrl: string;
  primaryText: string;
  radioOptions: ConfigureConnectionRadioOption[];
  manualConfigText: string;
  connectAccountsUrl: string;
  connectAccountsBtnText: string;
  authUrl: string;
  unlinkUrl: string;
  strategy: string;
  connectedWith: string;
  additionalHelpText: string;
}
export default defineComponent({
  props: {
    extensions: Array,
    configureConnectionProps: {
      type: Object,
      required: true,
    },
    isNoDataPage: Boolean,
    hasClientConfiguration: Boolean,
    indexActionUrl: String,
    authorizeUrl: String,
    forwardToAuthNonce: String,
    strategyToUse: String,
    isConfigured: Boolean,
  },
  components: {
    Notification,
    ContentBlock,
  },
  computed: {
    componentExtensions() {
      const entries = this.extensions as Array<ComponentExtension>;
      return markRaw(entries.map((ref) => useExternalPluginComponent(ref.plugin,
        ref.component)));
    },
    configConnectProps() {
      return this.configureConnectionProps as ConfigureConnectionProps;
    },
  },
});
</script>
