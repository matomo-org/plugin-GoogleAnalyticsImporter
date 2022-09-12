<template>
  <div v-form>
    <div>
      <Field
        uicontrol="radio"
        name="selectedImporter"
        :model-value="selectedImporter"
        @update:model-value="$emit('update:selectedImporter', 'ua')"
        :options="importOptionsUa"
        :inline-help="translate('GoogleAnalyticsImporter_SelectImporterUAInlineHelp')"
      >
      </Field>
      <Field
        uicontrol="radio"
        name="selectedImporter"
        :model-value="selectedImporterGa4"
        @update:model-value="$emit('update:selectedImporterGa4', 'ga4')"
        :options="importOptionsGa4"
        :inline-help="translate('GoogleAnalyticsImporter_SelectImporterGA4InlineHelp')"
      >
      </Field>
    </div>
  </div>
</template>
<script lang="ts">
import { defineComponent } from 'vue';
import { Form, Field } from 'CorePluginsAdmin';

interface ImportSelector {
  selectedImporter: string;
  selectedImporterGa4: string;
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
  emits: ['update:selectedImporter'],
  data(): ImportSelector {
    return {
      selectedImporter: '',
      selectedImporterGa4: '',
    };
  },
});
</script>
