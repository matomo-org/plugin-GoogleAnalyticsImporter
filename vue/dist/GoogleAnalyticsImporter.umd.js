(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else if(typeof define === 'function' && define.amd)
		define(["CoreHome", , "CorePluginsAdmin"], factory);
	else if(typeof exports === 'object')
		exports["GoogleAnalyticsImporter"] = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else
		root["GoogleAnalyticsImporter"] = factory(root["CoreHome"], root["Vue"], root["CorePluginsAdmin"]);
})((typeof self !== 'undefined' ? self : this), function(__WEBPACK_EXTERNAL_MODULE__19dc__, __WEBPACK_EXTERNAL_MODULE__8bbf__, __WEBPACK_EXTERNAL_MODULE_a5a2__) {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "plugins/GoogleAnalyticsImporter/vue/dist/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "fae3");
/******/ })
/************************************************************************/
/******/ ({

/***/ "19dc":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__19dc__;

/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__8bbf__;

/***/ }),

/***/ "a5a2":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_a5a2__;

/***/ }),

/***/ "c745":
/***/ (function(module, exports) {



/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "ImportScheduler", function() { return /* reexport */ ImportScheduler; });

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  var currentScript = window.document.currentScript
  if (false) { var getCurrentScript; }

  var src = currentScript && currentScript.src.match(/(.+\/)[^/]+\.js(\?.*)?$/)
  if (src) {
    __webpack_require__.p = src[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

// EXTERNAL MODULE: external {"commonjs":"vue","commonjs2":"vue","root":"Vue"}
var external_commonjs_vue_commonjs2_vue_root_Vue_ = __webpack_require__("8bbf");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/GoogleAnalyticsImporter/vue/src/ImportScheduler/ImportScheduler.vue?vue&type=template&id=2e25a54b

var _hoisted_1 = ["disabled"];
function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('GoogleAnalyticsImporter_ScheduleImportDesc1')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('GoogleAnalyticsImporter_ScheduleImportDesc2')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "text",
    name: "startDate",
    modelValue: _ctx.startDate,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
      return _ctx.startDate = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_StartDate'),
    placeholder: _ctx.translate('GoogleAnalyticsImporter_CreationDate'),
    "inline-help": _ctx.translate('GoogleAnalyticsImporter_StartDateHelp')
  }, null, 8, ["modelValue", "title", "placeholder", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "text",
    name: "endDate",
    modelValue: _ctx.endDate,
    "onUpdate:modelValue": _cache[1] || (_cache[1] = function ($event) {
      return _ctx.endDate = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_EndDate'),
    placeholder: _ctx.translate('GoogleAnalyticsImporter_None'),
    "inline-help": _ctx.endDateHelp
  }, null, 8, ["modelValue", "title", "placeholder", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "text",
    name: "propertyId",
    modelValue: _ctx.propertyId,
    "onUpdate:modelValue": _cache[2] || (_cache[2] = function ($event) {
      return _ctx.propertyId = $event;
    }),
    placeholder: "eg. UA-XXXXX-X",
    title: _ctx.translate('GoogleAnalyticsImporter_PropertyId'),
    "inline-help": _ctx.translate('GoogleAnalyticsImporter_PropertyIdHelp')
  }, null, 8, ["modelValue", "title", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "text",
    name: "accountId",
    placeholder: "eg. 1234567",
    modelValue: _ctx.accountId,
    "onUpdate:modelValue": _cache[3] || (_cache[3] = function ($event) {
      return _ctx.accountId = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_AccountId'),
    "inline-help": _ctx.translate('GoogleAnalyticsImporter_AccountIdHelp')
  }, null, 8, ["modelValue", "title", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "text",
    name: "viewId",
    placeholder: "eg. 1234567",
    modelValue: _ctx.viewId,
    "onUpdate:modelValue": _cache[4] || (_cache[4] = function ($event) {
      return _ctx.viewId = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_ViewId'),
    "inline-help": _ctx.translate('GoogleAnalyticsImporter_ViewIdHelp')
  }, null, 8, ["modelValue", "title", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "checkbox",
    name: "isMobileApp",
    modelValue: _ctx.isMobileApp,
    "onUpdate:modelValue": _cache[5] || (_cache[5] = function ($event) {
      return _ctx.isMobileApp = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_IsMobileApp'),
    "inline-help": _ctx.translate('GoogleAnalyticsImporter_IsMobileAppHelp')
  }, null, 8, ["modelValue", "title", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "text",
    name: "timezone",
    modelValue: _ctx.timezone,
    "onUpdate:modelValue": _cache[6] || (_cache[6] = function ($event) {
      return _ctx.timezone = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_Timezone'),
    placeholder: _ctx.translate('GoogleAnalyticsImporter_Optional'),
    "inline-help": _ctx.timezoneHelp
  }, null, 8, ["modelValue", "title", "placeholder", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "multituple",
    name: "extraCustomDimensions",
    modelValue: _ctx.extraCustomDimensions,
    "onUpdate:modelValue": _cache[7] || (_cache[7] = function ($event) {
      return _ctx.extraCustomDimensions = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_ExtraCustomDimensions'),
    "inline-help": _ctx.extraCustomDimensionsHelp,
    "ui-control-attributes": _ctx.extraCustomDimensionsField
  }, null, 8, ["modelValue", "title", "inline-help", "ui-control-attributes"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "checkbox",
    name: "forceIgnoreOutOfCustomDimSlotError",
    modelValue: _ctx.ignoreCustomDimensionSlotCheck,
    "onUpdate:modelValue": _cache[8] || (_cache[8] = function ($event) {
      return _ctx.ignoreCustomDimensionSlotCheck = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_ForceCustomDimensionSlotCheck'),
    "inline-help": _ctx.forceIgnoreOutOfCustomDimSlotErrorHelp
  }, null, 8, ["modelValue", "title", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h3", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('GoogleAnalyticsImporter_Troubleshooting')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
    uicontrol: "checkbox",
    name: "isVerboseLoggingEnabled",
    modelValue: _ctx.isVerboseLoggingEnabled,
    "onUpdate:modelValue": _cache[9] || (_cache[9] = function ($event) {
      return _ctx.isVerboseLoggingEnabled = $event;
    }),
    title: _ctx.translate('GoogleAnalyticsImporter_IsVerboseLoggingEnabled'),
    "inline-help": _ctx.isVerboseLoggingEnabledHelp
  }, null, 8, ["modelValue", "title", "inline-help"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "submit",
    id: "startImportSubmit",
    class: "btn",
    onClick: _cache[10] || (_cache[10] = function ($event) {
      return _ctx.startImport();
    }),
    disabled: _ctx.isStartingImport
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('GoogleAnalyticsImporter_Start')), 9, _hoisted_1)]);
}
// CONCATENATED MODULE: ./plugins/GoogleAnalyticsImporter/vue/src/ImportScheduler/ImportScheduler.vue?vue&type=template&id=2e25a54b

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// EXTERNAL MODULE: external "CorePluginsAdmin"
var external_CorePluginsAdmin_ = __webpack_require__("a5a2");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/GoogleAnalyticsImporter/vue/src/ImportScheduler/ImportScheduler.vue?vue&type=script&lang=ts



/* harmony default export */ var ImportSchedulervue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    startImportNonce: {
      type: String,
      required: true
    },
    maxEndDateDesc: String,
    extraCustomDimensionsField: {
      type: Array,
      required: true
    }
  },
  components: {
    Field: external_CorePluginsAdmin_["Field"]
  },
  data: function data() {
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
      timezone: ''
    };
  },
  created: function created() {
    return this;
  },
  methods: {
    startImport: function startImport() {
      if (this.startDate) {
        try {
          Object(external_CoreHome_["parseDate"])(this.startDate);
        } catch (e) {
          var instanceId = external_CoreHome_["NotificationsStore"].show({
            message: Object(external_CoreHome_["translate"])('GoogleAnalyticsImporter_InvalidDateFormat', ['YYYY-MM-DD']),
            context: 'error',
            type: 'transient'
          });
          external_CoreHome_["NotificationsStore"].scrollToNotification(instanceId);
          return undefined;
        }
      }

      this.isStartingImport = true;
      var forceCustomDimensionSlotCheck = !this.ignoreCustomDimensionSlotCheck;
      return external_CoreHome_["AjaxHelper"].post({
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
        extraCustomDimensions: this.extraCustomDimensions,
        isVerboseLoggingEnabled: this.isVerboseLoggingEnabled ? '1' : '0',
        forceCustomDimensionSlotCheck: forceCustomDimensionSlotCheck ? '1' : '0'
      }, {}, {
        withTokenInUrl: true
      }).finally(function () {
        window.location.reload();
      });
    }
  },
  computed: {
    endDateHelp: function endDateHelp() {
      var endDateHelp = Object(external_CoreHome_["translate"])('GoogleAnalyticsImporter_EndDateHelp');
      var maxEndDateDesc = this.maxEndDateDesc && Object(external_CoreHome_["translate"])('<br/><br/>GoogleAnalyticsImporter_MaxEndDateHelp', this.maxEndDateDesc);
      return "".concat(endDateHelp, " ").concat(maxEndDateDesc);
    },
    timezoneHelp: function timezoneHelp() {
      return Object(external_CoreHome_["translate"])('GoogleAnalyticsImporter_TimezoneHelp', '<a href="https://www.php.net/manual/en/timezones.php" rel="noreferrer noopener">', '</a>');
    },
    extraCustomDimensionsHelp: function extraCustomDimensionsHelp() {
      var link = 'https://ga-dev-tools.appspot.com/dimensions-metrics-explorer/';
      return Object(external_CoreHome_["translate"])('GoogleAnalyticsImporter_ExtraCustomDimensionsHelp', "<a href=\"".concat(link, "\" rel=\"noreferrer noopener\">"), '</a>');
    },
    forceIgnoreOutOfCustomDimSlotErrorHelp: function forceIgnoreOutOfCustomDimSlotErrorHelp() {
      return Object(external_CoreHome_["translate"])('GoogleAnalyticsImporter_ForceCustomDimensionSlotCheckHelp', '<a href="https://matomo.org/docs/custom-dimensions/" rel="noreferrer noopener">', '</a>');
    },
    isVerboseLoggingEnabledHelp: function isVerboseLoggingEnabledHelp() {
      return Object(external_CoreHome_["translate"])('GoogleAnalyticsImporter_IsVerboseLoggingEnabledHelp', '/path/to/matomo/tmp/logs/', 'gaimportlog.$idSite.$matomoDomain.log');
    }
  }
}));
// CONCATENATED MODULE: ./plugins/GoogleAnalyticsImporter/vue/src/ImportScheduler/ImportScheduler.vue?vue&type=script&lang=ts
 
// EXTERNAL MODULE: ./plugins/GoogleAnalyticsImporter/vue/src/ImportScheduler/ImportScheduler.vue?vue&type=custom&index=0&blockType=todo
var ImportSchedulervue_type_custom_index_0_blockType_todo = __webpack_require__("c745");
var ImportSchedulervue_type_custom_index_0_blockType_todo_default = /*#__PURE__*/__webpack_require__.n(ImportSchedulervue_type_custom_index_0_blockType_todo);

// CONCATENATED MODULE: ./plugins/GoogleAnalyticsImporter/vue/src/ImportScheduler/ImportScheduler.vue



ImportSchedulervue_type_script_lang_ts.render = render
/* custom blocks */

if (typeof ImportSchedulervue_type_custom_index_0_blockType_todo_default.a === 'function') ImportSchedulervue_type_custom_index_0_blockType_todo_default()(ImportSchedulervue_type_script_lang_ts)


/* harmony default export */ var ImportScheduler = (ImportSchedulervue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/GoogleAnalyticsImporter/vue/src/index.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js




/***/ })

/******/ });
});
//# sourceMappingURL=GoogleAnalyticsImporter.umd.js.map