<template>
  <div v-form>
    <div>
      <Field
        uicontrol="radio"
        name="selectedImporter"
        v-model="selectedImporter"
        :options="importOptionsUa"
        :inline-help="translate('GoogleAnalyticsImporter_SelectImporterUAInlineHelpText')"
      >
      </Field>
      <Field
        uicontrol="radio"
        name="selectedImporter"
        v-model="selectedImporterGA4"
        :options="importOptionsGa4"
        :inline-help="translate('GoogleAnalyticsImporter_SelectImporterGA4InlineHelpText')"
      >
      </Field>
    </div>
  </div>
</template>
<script lang="ts">
import { defineComponent } from 'vue';
import { Form, Field } from 'CorePluginsAdmin';

interface ImportSelector {
  selectedImporter: string,
  selectedImporterGA4: string,
}

export default defineComponent({
  props: {
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
    Field,
  },
  directives: {
    Form,
  },
  data() : ImportSelector {
    return {
      selectedImporter: '',
      selectedImporterGA4: '',
    };
  },
});
</script>
