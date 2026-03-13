(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else if(typeof define === 'function' && define.amd)
		define(["CoreHome", , "CorePluginsAdmin"], factory);
	else if(typeof exports === 'object')
		exports["OAuth2"] = factory(require("CoreHome"), require("vue"), require("CorePluginsAdmin"));
	else
		root["OAuth2"] = factory(root["CoreHome"], root["Vue"], root["CorePluginsAdmin"]);
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
/******/ 	__webpack_require__.p = "plugins/OAuth2/vue/dist/";
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

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "Oauth2AdminApp", function() { return /* reexport */ AdminApp; });

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

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/OAuth2/vue/src/AdminApp.vue?vue&type=template&id=f80da52a

const _hoisted_1 = {
  class: "oauth2-admin"
};
const _hoisted_2 = {
  key: 0,
  class: "alert alert-warning"
};
const _hoisted_3 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("strong", null, "Client secret:", -1);
const _hoisted_4 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
  class: "form-help"
}, "Copy now; it will not be shown again.", -1);
const _hoisted_5 = {
  class: "ui-confirm",
  ref: "confirmDeleteClient"
};
const _hoisted_6 = ["value"];
const _hoisted_7 = ["value"];
const _hoisted_8 = {
  class: "ui-confirm",
  ref: "confirmRotateClient"
};
const _hoisted_9 = ["value"];
const _hoisted_10 = ["value"];
const _hoisted_11 = {
  key: 0,
  class: "card card-table entityTable"
};
const _hoisted_12 = ["title"];
const _hoisted_13 = ["onClick", "title"];
const _hoisted_14 = ["onClick", "title"];
const _hoisted_15 = {
  key: 1
};
const _hoisted_16 = {
  class: "row"
};
const _hoisted_17 = {
  class: "row"
};
const _hoisted_18 = {
  class: "row"
};
const _hoisted_19 = {
  class: "row"
};
const _hoisted_20 = {
  class: "row"
};
const _hoisted_21 = {
  class: "row"
};
const _hoisted_22 = {
  class: "row"
};
const _hoisted_23 = ["disabled"];
function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_1, [_ctx.secret ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_2, [_hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("code", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.secret), 1), _hoisted_4])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.confirmDeleteLabel), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    role: "yes",
    type: "button",
    value: _ctx.translate('General_Yes')
  }, null, 8, _hoisted_6), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    role: "no",
    type: "button",
    value: _ctx.translate('General_No')
  }, null, 8, _hoisted_7)], 512), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.confirmRotateLabel), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    role: "yes",
    type: "button",
    value: _ctx.translate('General_Yes')
  }, null, 8, _hoisted_9), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    role: "no",
    type: "button",
    value: _ctx.translate('General_No')
  }, null, 8, _hoisted_10)], 512), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, {
    "content-title": _ctx.translate('OAuth2_AdminHeading'),
    feature: _ctx.translate('OAuth2_AdminHeading')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientsDescriptions')), 1), _ctx.clients && _ctx.clients.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("table", _hoisted_11, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("thead", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminName')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientId')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientCreatedAt')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientType')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientGrants')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientRedirects')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientActions')), 1)])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tbody", null, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.clients, client => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", {
        key: client.client_id
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", {
        title: client.description
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("strong", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(client.name), 1)], 8, _hoisted_12), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("code", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(client.client_id), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(client.created_at), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.type_options[client.type]), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])((client.grant_types || []).join(', ')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(client.redirect_uris || [], uri => {
        return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
          key: uri
        }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("code", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(uri), 1)]);
      }), 128))]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        class: "table-action icon-refresh",
        onClick: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.rotateSecret(client), ["prevent"]),
        title: _ctx.translate('OAuth2_AdminRotateSecret')
      }, null, 8, _hoisted_13), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        class: "table-action icon-delete",
        onClick: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.deleteClient(client), ["prevent"]),
        title: _ctx.translate('OAuth2_AdminDelete')
      }, null, 8, _hoisted_14)])]);
    }), 128))])])) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_15, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminNoClients')), 1))]),
    _: 1
  }, 8, ["content-title", "feature"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, {
    "content-title": _ctx.translate('OAuth2_AdminCreateTitle')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("form", {
      onSubmit: _cache[6] || (_cache[6] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.createClient && _ctx.createClient(...args), ["prevent"]))
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_16, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "text",
      name: "name",
      modelValue: _ctx.form.name,
      "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.form.name = $event),
      "inline-help": _ctx.translate('OAuth2_AdminNameHelp'),
      title: _ctx.translate('OAuth2_AdminName')
    }, null, 8, ["modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_17, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "textarea",
      name: "description",
      modelValue: _ctx.form.description,
      "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => _ctx.form.description = $event),
      "inline-help": _ctx.translate('OAuth2_AdminDescriptionHelp'),
      title: _ctx.translate('OAuth2_AdminDescription')
    }, null, 8, ["modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_18, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "select",
      name: "type",
      modelValue: _ctx.form.type,
      "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => _ctx.form.type = $event),
      title: _ctx.translate('OAuth2_AdminType'),
      "inline-help": _ctx.translate('OAuth2_AdminTypeHelp', '<strong>', '</strong>'),
      options: {
        confidential: _ctx.translate('OAuth2_AdminConfidential'),
        public: _ctx.translate('OAuth2_AdminPublic')
      }
    }, null, 8, ["modelValue", "title", "inline-help", "options"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_19, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "checkbox",
      options: _ctx.visibleGrantOptions,
      "var-type": "array",
      name: "grant_types",
      modelValue: _ctx.form.grant_types,
      "onUpdate:modelValue": _cache[3] || (_cache[3] = $event => _ctx.form.grant_types = $event),
      "inline-help": _ctx.translate('OAuth2_AdminGrantTypesHelp'),
      title: _ctx.translate('OAuth2_AdminClientGrants')
    }, null, 8, ["options", "modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_20, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "select",
      options: _ctx.scopes,
      name: "scopes",
      modelValue: _ctx.form.scope,
      "onUpdate:modelValue": _cache[4] || (_cache[4] = $event => _ctx.form.scope = $event),
      "inline-help": _ctx.translate('OAuth2_AdminScopeHelp', '<strong>', '</strong>'),
      title: _ctx.translate('OAuth2_AdminScope')
    }, null, 8, ["options", "modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_21, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "textarea",
      name: "redirect_uris",
      modelValue: _ctx.form.redirect_uris,
      "onUpdate:modelValue": _cache[5] || (_cache[5] = $event => _ctx.form.redirect_uris = $event),
      placeholder: "https://example.com/callback",
      "inline-help": _ctx.translate('OAuth2_AdminRedirectUrisHelp'),
      title: _ctx.translate('OAuth2_AdminRedirectUris')
    }, null, 8, ["modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_22, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "submit",
      class: "btn",
      disabled: _ctx.loading
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminSave')), 9, _hoisted_23)])], 32)]),
    _: 1
  }, 8, ["content-title"])]);
}
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/AdminApp.vue?vue&type=template&id=f80da52a

// EXTERNAL MODULE: external "CorePluginsAdmin"
var external_CorePluginsAdmin_ = __webpack_require__("a5a2");

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/OAuth2/vue/src/AdminApp.vue?vue&type=script&lang=ts



const notificationId = 'oauth2clientcreate';
/* harmony default export */ var AdminAppvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'Oauth2AdminApp',
  props: {
    initialClients: {
      type: Array,
      required: true
    },
    scopes: {
      type: Object,
      required: true
    }
  },
  components: {
    Field: external_CorePluginsAdmin_["Field"],
    ContentBlock: external_CoreHome_["ContentBlock"]
  },
  data() {
    const typeOptions = {
      confidential: this.translate('OAuth2_AdminConfidential'),
      public: this.translate('OAuth2_AdminPublic')
    };
    const grantOptions = {
      authorization_code: this.translate('OAuth2_AdminGrantAuthorizationCode'),
      client_credentials: this.translate('OAuth2_AdminGrantClientCredentials'),
      refresh_token: this.translate('OAuth2_AdminGrantRefreshToken')
    };
    return {
      clients: this.initialClients || [],
      loading: false,
      secret: '',
      confirmDeleteLabel: '',
      confirmRotateLabel: '',
      grant_options: grantOptions,
      type_options: typeOptions,
      form: {
        name: '',
        description: '',
        type: 'confidential',
        grant_types: ['authorization_code', 'client_credentials', 'refresh_token'],
        scope: '',
        redirect_uris: '',
        active: true
      }
    };
  },
  computed: {
    visibleGrantOptions() {
      if (this.form.type === 'public') {
        const filtered = {};
        if (this.grant_options.authorization_code) {
          filtered.authorization_code = this.grant_options.authorization_code;
        }
        if (this.grant_options.refresh_token) {
          filtered.refresh_token = this.grant_options.refresh_token;
        }
        return filtered;
      }
      return this.grant_options;
    }
  },
  watch: {
    'form.type': 'onFormTypeChange'
  },
  methods: {
    onFormTypeChange(newType) {
      if (newType === 'public' && this.form.grant_types.includes('client_credentials')) {
        this.form.grant_types = this.form.grant_types.filter(value => value !== 'client_credentials');
      }
    },
    showSuccessNotification(method, message) {
      const instanceId = external_CoreHome_["NotificationsStore"].show({
        id: `OAuth2_${method}`,
        type: 'transient',
        context: 'success',
        message
      });
      setTimeout(() => {
        external_CoreHome_["NotificationsStore"].scrollToNotification(instanceId);
      });
    },
    async fetchClients() {
      this.loading = true;
      try {
        await external_CoreHome_["AjaxHelper"].fetch({
          method: 'OAuth2.getClients',
          filter_limit: '-1'
        }).then(clients => {
          this.clients = clients;
        });
      } finally {
        this.loading = false;
      }
    },
    async createClient() {
      this.removeAnyClientNotification();
      if (!this.checkRequiredFieldsAreSet()) {
        return;
      }
      this.loading = true;
      this.secret = '';
      const params = {
        method: 'OAuth2.createClient',
        name: this.form.name,
        description: this.form.description,
        type: this.form.type,
        grantTypes: this.form.grant_types,
        scope: this.form.scope,
        redirectUris: this.form.redirect_uris,
        active: 1
      };
      try {
        await external_CoreHome_["AjaxHelper"].fetch(params).then(response => {
          this.clients.push(response.client);
          const message = this.translate('OAuth2_AdminCreated', response.client.client_id);
          this.showSuccessNotification('createClient', message);
          if (response.secret) {
            this.secret = response.secret;
          }
          this.resetForm();
        });
      } finally {
        this.loading = false;
      }
    },
    async rotateSecret(client) {
      if (!client) {
        return;
      }
      this.confirmRotateLabel = this.translate('OAuth2_AdminRotateConfirm', (client === null || client === void 0 ? void 0 : client.name) || (client === null || client === void 0 ? void 0 : client.client_id));
      external_CoreHome_["Matomo"].helper.modalConfirm(this.$refs.confirmRotateClient, {
        yes: () => {
          this.loading = true;
          try {
            external_CoreHome_["AjaxHelper"].fetch({
              method: 'OAuth2.rotateSecret',
              clientId: client.client_id
            }).then(response => {
              if (response && response.secret) {
                this.secret = response.secret;
                const message = this.translate('OAuth2_AdminRotated', client.client_id);
                this.showSuccessNotification('rotateSecret', message);
              }
            });
          } finally {
            this.loading = false;
          }
        }
      });
    },
    async deleteClient(client) {
      if (!client) {
        return;
      }
      this.confirmDeleteLabel = this.translate('OAuth2_AdminDeleteConfirm', (client === null || client === void 0 ? void 0 : client.name) || (client === null || client === void 0 ? void 0 : client.client_id));
      external_CoreHome_["Matomo"].helper.modalConfirm(this.$refs.confirmDeleteClient, {
        yes: () => {
          this.loading = true;
          try {
            external_CoreHome_["AjaxHelper"].fetch({
              method: 'OAuth2.deleteClient',
              clientId: client.client_id
            }).then(response => {
              if (response.deleted) {
                this.clients = this.clients.filter(c => c.client_id !== client.client_id);
                const message = this.translate('OAuth2_AdminDeleted', client.client_id);
                this.showSuccessNotification('deleteClient', message);
              }
            });
          } finally {
            this.loading = false;
          }
        }
      });
    },
    resetForm() {
      this.form.name = '';
      this.form.description = '';
      this.form.type = 'confidential';
      this.form.grant_types = ['authorization_code', 'client_credentials', 'refresh_token'];
      this.form.scope = '';
      this.form.redirect_uris = '';
      this.form.active = true;
    },
    checkRequiredFieldsAreSet() {
      let response = true;
      let errorMessage = '';
      if (!this.form.name.trim()) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminName');
      } else if (!this.form.type.trim()) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminType');
      } else if (!this.form.grant_types.length) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminClientGrants');
      } else if (!this.form.scope.trim()) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminScope');
      } else if (!this.form.redirect_uris.trim() && this.form.grant_types.includes('authorization_code')) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminRedirectUris');
      }
      if (!response && errorMessage) {
        this.showErrorFieldNotProvidedNotification(errorMessage);
      }
      return response;
    },
    removeAnyClientNotification() {
      external_CoreHome_["NotificationsStore"].remove(notificationId);
      external_CoreHome_["NotificationsStore"].remove('ajaxHelper');
    },
    showNotification(message, context, type = null) {
      const notificationInstanceId = external_CoreHome_["NotificationsStore"].show({
        message,
        context,
        id: notificationId,
        type: type !== null ? type : 'toast'
      });
      setTimeout(() => {
        external_CoreHome_["NotificationsStore"].scrollToNotification(notificationInstanceId);
      }, 200);
    },
    showErrorFieldNotProvidedNotification(title) {
      const message = this.translate('OAuth2_ErrorXNotProvided', [title]);
      this.showNotification(message, 'error');
    }
  }
}));
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/AdminApp.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/AdminApp.vue



AdminAppvue_type_script_lang_ts.render = render

/* harmony default export */ var AdminApp = (AdminAppvue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/index.ts
/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js




/***/ })

/******/ });
});
//# sourceMappingURL=OAuth2.umd.js.map