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

/***/ "054d":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "19dc":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__19dc__;

/***/ }),

/***/ "6c4a":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "7a8a":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_7_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_7_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_7_oneOf_1_2_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_Edit_vue_vue_type_style_index_0_id_02b0c366_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("6c4a");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_7_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_7_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_7_oneOf_1_2_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_Edit_vue_vue_type_style_index_0_id_02b0c366_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_7_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_7_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_7_oneOf_1_2_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_Edit_vue_vue_type_style_index_0_id_02b0c366_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__8bbf__;

/***/ }),

/***/ "a5a2":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE_a5a2__;

/***/ }),

/***/ "df6d":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_7_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_7_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_7_oneOf_1_2_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_List_vue_vue_type_style_index_0_id_19ab60c1_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("054d");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_7_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_7_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_7_oneOf_1_2_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_List_vue_vue_type_style_index_0_id_19ab60c1_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_7_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_7_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_7_oneOf_1_2_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_1_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_1_1_List_vue_vue_type_style_index_0_id_19ab60c1_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "Oauth2AdminApp", function() { return /* reexport */ Manage; });

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

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/OAuth2/vue/src/OAuthClients/Manage.vue?vue&type=template&id=096198f6

const _hoisted_1 = {
  class: "oauth2-admin"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Oauth2ClientList = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Oauth2ClientList");
  const _component_Oauth2ClientEdit = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Oauth2ClientEdit");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_1, [!_ctx.isEditMode ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Oauth2ClientList, {
    key: 0,
    clients: _ctx.clients,
    scopes: _ctx.scopes,
    "authorize-url": _ctx.authorizeUrl,
    "token-url": _ctx.tokenUrl,
    onCreate: _ctx.createClient,
    onEdit: _ctx.editClient,
    onDeleted: _ctx.onClientDeleted,
    onUpdated: _ctx.onClientUpdated
  }, null, 8, ["clients", "scopes", "authorize-url", "token-url", "onCreate", "onEdit", "onDeleted", "onUpdated"])) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Oauth2ClientEdit, {
    key: 1,
    "client-id": _ctx.editedClientId,
    scopes: _ctx.scopes,
    "initial-secret": _ctx.secret,
    onCancel: _ctx.showList,
    onSaved: _ctx.onClientSaved
  }, null, 8, ["client-id", "scopes", "initial-secret", "onCancel", "onSaved"]))]);
}
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/Manage.vue?vue&type=template&id=096198f6

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/OAuth2/vue/src/OAuthClients/List.vue?vue&type=template&id=19ab60c1&scoped=true

const _withScopeId = n => (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["pushScopeId"])("data-v-19ab60c1"), n = n(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["popScopeId"])(), n);
const Listvue_type_template_id_19ab60c1_scoped_true_hoisted_1 = {
  class: "oauth2-admin oauth2-admin-list"
};
const _hoisted_2 = {
  class: "ui-confirm",
  ref: "confirmToggleClient"
};
const _hoisted_3 = ["value"];
const _hoisted_4 = ["value"];
const _hoisted_5 = ["innerHTML"];
const _hoisted_6 = {
  style: {
    "width": "180px"
  }
};
const _hoisted_7 = {
  key: 0
};
const _hoisted_8 = ["title"];
const _hoisted_9 = {
  class: "client-id-code"
};
const _hoisted_10 = {
  class: "redirect-uri"
};
const _hoisted_11 = {
  class: "created-at"
};
const _hoisted_12 = ["onClick", "title"];
const _hoisted_13 = ["onClick", "title"];
const _hoisted_14 = ["onClick", "title"];
const _hoisted_15 = {
  key: 1
};
const _hoisted_16 = {
  colspan: "9"
};
const _hoisted_17 = {
  class: "tableActionBar"
};
const _hoisted_18 = /*#__PURE__*/_withScopeId(() => /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "icon-add"
}, null, -1));
function Listvue_type_template_id_19ab60c1_scoped_true_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_PasswordConfirmation = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("PasswordConfirmation");
  const _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");
  const _directive_content_table = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("content-table");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Listvue_type_template_id_19ab60c1_scoped_true_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_PasswordConfirmation, {
    modelValue: _ctx.showDeleteConfirmModal,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.showDeleteConfirmModal = $event),
    onConfirmed: _ctx.onDeleteConfirmed
  }, null, 8, ["modelValue", "onConfirmed"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("h2", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.confirmToggleLabel), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    role: "yes",
    type: "button",
    value: _ctx.translate('General_Yes')
  }, null, 8, _hoisted_3), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    role: "no",
    type: "button",
    value: _ctx.translate('General_No')
  }, null, 8, _hoisted_4)], 512), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, {
    "content-title": _ctx.translate('OAuth2_AdminHeading'),
    feature: _ctx.translate('OAuth2_AdminHeading')
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("p", {
      innerHTML: _ctx.$sanitize(_ctx.adminClientsDescription)
    }, null, 8, _hoisted_5), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("table", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("thead", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminName')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientType')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientGrants')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminScope')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientStatus')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientId')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientRedirects')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientCreatedAt')), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("th", _hoisted_6, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminClientActions')), 1)])]), _ctx.clients.length ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tbody", _hoisted_7, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.clients, client => {
      return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tr", {
        key: client.client_id
      }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", {
        title: _ctx.$sanitize(client.description)
      }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(client.name), 9, _hoisted_8), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.typeOptions[client.type]), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(client.grant_types || [], grantType => {
        return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
          key: grantType
        }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.getGrantTypeLabel(grantType)), 1);
      }), 128))]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.getScopeLabel(client)), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(client.active ? _ctx.translate('OAuth2_AdminActive') : _ctx.translate('OAuth2_AdminDisabled')), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("code", _hoisted_9, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(client.client_id), 1)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(client.redirect_uris || [], uri => {
        return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", {
          key: uri
        }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("code", _hoisted_10, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(uri), 1)]);
      }), 128))]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", _hoisted_11, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(client.created_at), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(`table-action ${client.active ? 'icon-pause' : 'icon-play'}`),
        onClick: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.toggleClientStatus(client), ["prevent"]),
        title: client.active ? _ctx.translate('OAuth2_AdminPause') : _ctx.translate('OAuth2_AdminResume')
      }, null, 10, _hoisted_12), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        class: "table-action icon-edit",
        onClick: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.$emit('edit', client.client_id), ["prevent"]),
        title: _ctx.translate('OAuth2_AdminEdit')
      }, null, 8, _hoisted_13), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
        class: "table-action icon-delete",
        onClick: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.deleteClient(client), ["prevent"]),
        title: _ctx.translate('OAuth2_AdminDelete')
      }, null, 8, _hoisted_14)])]);
    }), 128))])) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("tbody", _hoisted_15, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("tr", null, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("td", _hoisted_16, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminNoClients')), 1)])]))])), [[_directive_content_table]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_17, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
      class: "createNewClient",
      onClick: _cache[1] || (_cache[1] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.$emit('create'), ["prevent"]))
    }, [_hoisted_18, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminCreateTitle')), 1)])])]),
    _: 1
  }, 8, ["content-title", "feature"])]);
}
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/List.vue?vue&type=template&id=19ab60c1&scoped=true

// EXTERNAL MODULE: external "CorePluginsAdmin"
var external_CorePluginsAdmin_ = __webpack_require__("a5a2");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/OAuth2/vue/src/OAuthClients/List.vue?vue&type=script&lang=ts



const notificationId = 'oauth2clientlist';
/* harmony default export */ var Listvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'Oauth2ClientList',
  props: {
    clients: {
      type: Array,
      required: true
    },
    scopes: {
      type: Object,
      required: true
    },
    authorizeUrl: {
      type: String,
      required: true
    },
    tokenUrl: {
      type: String,
      required: true
    }
  },
  emits: ['create', 'edit', 'deleted', 'updated'],
  components: {
    ContentBlock: external_CoreHome_["ContentBlock"],
    PasswordConfirmation: external_CorePluginsAdmin_["PasswordConfirmation"]
  },
  directives: {
    ContentTable: external_CoreHome_["ContentTable"]
  },
  data() {
    return {
      confirmDeleteLabel: '',
      confirmToggleLabel: '',
      clientToDelete: null,
      showDeleteConfirmModal: false,
      typeOptions: {
        confidential: this.translate('OAuth2_AdminConfidential'),
        public: this.translate('OAuth2_AdminPublic')
      },
      grantTypeOptions: {
        authorization_code: this.translate('OAuth2_AdminGrantAuthorizationCode'),
        client_credentials: this.translate('OAuth2_AdminGrantClientCredentials'),
        refresh_token: this.translate('OAuth2_AdminGrantRefreshToken')
      }
    };
  },
  computed: {
    adminClientsDescription() {
      const authorizeUrl = `<a href="${this.authorizeUrl}"><code>${this.authorizeUrl}</code></a>`;
      const tokenUrl = `<a href="${this.tokenUrl}"><code>${this.tokenUrl}</code></a>`;
      return `${this.translate('OAuth2_AdminClientsDescriptions')} ${this.translate('OAuth2_AdminClientDescriptionAdditionalHelpText', authorizeUrl, tokenUrl)}`;
    }
  },
  methods: {
    getShortScopeLabel(scope) {
      const shortScopeLabels = {
        'matomo:read': this.translate('UsersManager_PrivView'),
        'matomo:write': this.translate('UsersManager_PrivWrite'),
        'matomo:admin': this.translate('UsersManager_PrivAdmin'),
        'matomo:superuser': this.translate('OAuth2_ScopeSuperUserShort')
      };
      return shortScopeLabels[scope] || this.scopes[scope] || scope;
    },
    showNotification(message, context, type = null) {
      const instanceId = external_CoreHome_["NotificationsStore"].show({
        message,
        context,
        id: notificationId,
        type: type !== null ? type : 'toast'
      });
      setTimeout(() => {
        external_CoreHome_["NotificationsStore"].scrollToNotification(instanceId);
      }, 200);
    },
    removeNotifications() {
      external_CoreHome_["NotificationsStore"].remove(notificationId);
      external_CoreHome_["NotificationsStore"].remove('ajaxHelper');
    },
    getScopeLabel(client) {
      var _client$scopes;
      const scope = (_client$scopes = client.scopes) === null || _client$scopes === void 0 ? void 0 : _client$scopes[0];
      if (!scope) {
        return '';
      }
      return this.getShortScopeLabel(scope);
    },
    getGrantTypeLabel(grantType) {
      return this.grantTypeOptions[grantType] || grantType;
    },
    toggleClientStatus(client) {
      const safeClientName = external_CoreHome_["Matomo"].helper.htmlEntities(client.name || client.client_id);
      this.confirmToggleLabel = client.active ? this.translate('OAuth2_AdminPauseConfirm', safeClientName) : this.translate('OAuth2_AdminResumeConfirm', safeClientName);
      external_CoreHome_["Matomo"].helper.modalConfirm(this.$refs.confirmToggleClient, {
        yes: () => {
          external_CoreHome_["AjaxHelper"].fetch({
            method: 'OAuth2.setClientActive',
            clientId: client.client_id,
            active: client.active ? '0' : '1'
          }).then(response => {
            if (response !== null && response !== void 0 && response.client) {
              this.removeNotifications();
              const safeUpdatedClientName = external_CoreHome_["Matomo"].helper.htmlEntities(response.client.name || response.client.client_id);
              this.showNotification(response.client.active ? this.translate('OAuth2_AdminResumed', safeUpdatedClientName) : this.translate('OAuth2_AdminPaused', safeUpdatedClientName), 'success');
              this.$emit('updated', response.client);
            }
          });
        }
      });
    },
    deleteClient(client) {
      const safeClientName = external_CoreHome_["Matomo"].helper.htmlEntities(client.name || client.client_id);
      this.confirmDeleteLabel = this.translate('OAuth2_AdminDeleteConfirm', safeClientName);
      this.clientToDelete = client;
      this.showDeleteConfirmModal = true;
    },
    onDeleteConfirmed(passwordConfirmation) {
      this.showDeleteConfirmModal = false;
      const client = this.clientToDelete;
      this.clientToDelete = null;
      if (!client) {
        return;
      }
      const safeClientName = external_CoreHome_["Matomo"].helper.htmlEntities(client.name || client.client_id);
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'OAuth2.deleteClient',
        clientId: client.client_id
      }, {
        postParams: {
          passwordConfirmation
        }
      }).then(response => {
        if (response !== null && response !== void 0 && response.deleted) {
          this.removeNotifications();
          this.showNotification(this.translate('OAuth2_AdminDeleted', safeClientName), 'success');
          this.$emit('deleted', client.client_id);
        }
      });
    }
  }
}));
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/List.vue?vue&type=script&lang=ts
 
// EXTERNAL MODULE: ./plugins/OAuth2/vue/src/OAuthClients/List.vue?vue&type=style&index=0&id=19ab60c1&scoped=true&lang=css
var Listvue_type_style_index_0_id_19ab60c1_scoped_true_lang_css = __webpack_require__("df6d");

// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/List.vue





Listvue_type_script_lang_ts.render = Listvue_type_template_id_19ab60c1_scoped_true_render
Listvue_type_script_lang_ts.__scopeId = "data-v-19ab60c1"

/* harmony default export */ var List = (Listvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--13-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/OAuth2/vue/src/OAuthClients/Edit.vue?vue&type=template&id=02b0c366&scoped=true

const Editvue_type_template_id_02b0c366_scoped_true_withScopeId = n => (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["pushScopeId"])("data-v-02b0c366"), n = n(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["popScopeId"])(), n);
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_1 = {
  class: "oauth2-admin oauth2-admin-edit"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_2 = {
  key: 0
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_3 = {
  class: "loadingPiwik"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_4 = /*#__PURE__*/Editvue_type_template_id_02b0c366_scoped_true_withScopeId(() => /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("img", {
  src: "plugins/Morpheus/images/loading-blue.gif"
}, null, -1));
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_5 = {
  class: "row"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_6 = {
  class: "row"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_7 = {
  key: 0,
  class: "row"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_8 = {
  key: 1,
  class: "row oauth2-secret-head"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_9 = {
  class: "col s12"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_10 = {
  key: 2,
  class: "oauth2-secret-div form-group row matomo-form-field"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_11 = {
  class: "col s12 m6"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_12 = {
  class: "copy-secret-wrapper-div"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_13 = {
  key: 0,
  class: "client-secret-code"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_14 = {
  key: 1,
  class: "client-secret-code"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_15 = {
  class: "col s12 m6"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_16 = ["innerHTML"];
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_17 = {
  class: "row",
  name: "type"
};
const Editvue_type_template_id_02b0c366_scoped_true_hoisted_18 = {
  class: "row",
  name: "grantType"
};
const _hoisted_19 = {
  class: "row",
  name: "scopes"
};
const _hoisted_20 = {
  class: "row"
};
const _hoisted_21 = {
  class: "row"
};
const _hoisted_22 = ["disabled"];
const _hoisted_23 = {
  class: "entityCancel"
};
function Editvue_type_template_id_02b0c366_scoped_true_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_PasswordConfirmation = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("PasswordConfirmation");
  const _component_Field = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Field");
  const _component_ContentBlock = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ContentBlock");
  const _directive_copy_to_clipboard = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveDirective"])("copy-to-clipboard");
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_PasswordConfirmation, {
    modelValue: _ctx.showPasswordConfirmModal,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => _ctx.showPasswordConfirmModal = $event),
    onConfirmed: _ctx.onPasswordConfirmed
  }, null, 8, ["modelValue", "onConfirmed"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ContentBlock, {
    "content-title": _ctx.contentTitle
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(() => [_ctx.loading ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("p", Editvue_type_template_id_02b0c366_scoped_true_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", Editvue_type_template_id_02b0c366_scoped_true_hoisted_3, [Editvue_type_template_id_02b0c366_scoped_true_hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" " + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_LoadingData')), 1)])])) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("form", {
      key: 1,
      onSubmit: _cache[9] || (_cache[9] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.submit && _ctx.submit(...args), ["prevent"]))
    }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_5, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "text",
      name: "name",
      modelValue: _ctx.form.name,
      "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => _ctx.form.name = $event),
      "inline-help": _ctx.translate('OAuth2_AdminNameHelp'),
      title: _ctx.translate('OAuth2_AdminName')
    }, null, 8, ["modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_6, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "textarea",
      name: "description",
      modelValue: _ctx.form.description,
      "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => _ctx.form.description = $event),
      rows: 1,
      "ui-control-attributes": {
        style: 'min-height: auto;'
      },
      "inline-help": _ctx.translate('OAuth2_AdminDescriptionHelp'),
      title: _ctx.translate('OAuth2_AdminDescription'),
      placeholder: _ctx.translate('OAuth2_AdminDescriptionPlaceholder')
    }, null, 8, ["modelValue", "inline-help", "title", "placeholder"])]), _ctx.isEditMode ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_7, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "text",
      name: "client_id",
      "model-value": _ctx.clientId,
      title: _ctx.translate('OAuth2_AdminClientId'),
      disabled: true
    }, null, 8, ["model-value", "title"])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.showSecretPanel ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_8, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("label", Editvue_type_template_id_02b0c366_scoped_true_hoisted_9, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_ClientSecret')) + " ", 1), _ctx.canRegenerateSecret ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("a", {
      key: 0,
      onClick: _cache[3] || (_cache[3] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])((...args) => _ctx.rotateSecret && _ctx.rotateSecret(...args), ["prevent"]))
    }, " (" + Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('OAuth2_AdminRotateSecret')) + ") ", 1)) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.showSecretPanel ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_10, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_11, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_12, [_ctx.visibleSecret ? Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])((Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("pre", Editvue_type_template_id_02b0c366_scoped_true_hoisted_13, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.displayedSecret), 1)])), [[_directive_copy_to_clipboard, {}]]) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("pre", Editvue_type_template_id_02b0c366_scoped_true_hoisted_14, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.displayedSecret), 1))])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_15, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
      class: "form-help",
      innerHTML: _ctx.$sanitize(_ctx.secretInlineHelp)
    }, null, 8, Editvue_type_template_id_02b0c366_scoped_true_hoisted_16)])])) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_17, [!_ctx.isEditMode ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
      key: 0,
      uicontrol: "select",
      name: "type",
      modelValue: _ctx.form.type,
      "onUpdate:modelValue": _cache[4] || (_cache[4] = $event => _ctx.form.type = $event),
      title: _ctx.translate('OAuth2_AdminType'),
      "inline-help": _ctx.translate('OAuth2_AdminTypeHelp', '<strong>', '</strong>'),
      options: _ctx.typeOptions
    }, null, 8, ["modelValue", "title", "inline-help", "options"])) : (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Field, {
      key: 1,
      uicontrol: "text",
      name: "type",
      "model-value": _ctx.typeOptions[_ctx.form.type] || _ctx.form.type,
      title: _ctx.translate('OAuth2_AdminType'),
      disabled: true
    }, null, 8, ["model-value", "title"]))]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", Editvue_type_template_id_02b0c366_scoped_true_hoisted_18, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "checkbox",
      options: _ctx.visibleGrantOptions,
      "var-type": "array",
      name: "grant_types",
      modelValue: _ctx.form.grant_types,
      "onUpdate:modelValue": _cache[5] || (_cache[5] = $event => _ctx.form.grant_types = $event),
      "inline-help": _ctx.translate('OAuth2_AdminGrantTypesHelp'),
      title: _ctx.translate('OAuth2_AdminClientGrants')
    }, null, 8, ["options", "modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_19, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "select",
      options: _ctx.scopes,
      name: "scopes",
      modelValue: _ctx.form.scope,
      "onUpdate:modelValue": _cache[6] || (_cache[6] = $event => _ctx.form.scope = $event),
      "inline-help": _ctx.translate('OAuth2_AdminScopeHelp', '<strong>', '</strong>'),
      title: _ctx.translate('OAuth2_AdminScope')
    }, null, 8, ["options", "modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_20, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Field, {
      uicontrol: "textarea",
      name: "redirect_uris",
      modelValue: _ctx.form.redirect_uris,
      "onUpdate:modelValue": _cache[7] || (_cache[7] = $event => _ctx.form.redirect_uris = $event),
      placeholder: "https://example.com/callback",
      "inline-help": _ctx.translate('OAuth2_AdminRedirectUrisHelp'),
      title: _ctx.translate('OAuth2_AdminRedirectUris')
    }, null, 8, ["modelValue", "inline-help", "title"])]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_21, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
      type: "submit",
      class: "btn",
      disabled: _ctx.loading
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.submitLabel), 9, _hoisted_22)]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_23, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("a", {
      onClick: _cache[8] || (_cache[8] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])($event => _ctx.$emit('cancel'), ["prevent"]))
    }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.translate('General_Cancel')), 1)])], 32))]),
    _: 1
  }, 8, ["content-title"])]);
}
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/Edit.vue?vue&type=template&id=02b0c366&scoped=true

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/OAuth2/vue/src/OAuthClients/Edit.vue?vue&type=script&lang=ts



const Editvue_type_script_lang_ts_notificationId = 'oauth2clientedit';
function getDefaultForm(scopes) {
  const firstScope = Object.keys(scopes || {})[0] || '';
  return {
    name: '',
    description: '',
    type: 'confidential',
    grant_types: ['authorization_code', 'client_credentials', 'refresh_token'],
    scope: firstScope,
    redirect_uris: '',
    active: true
  };
}
/* harmony default export */ var Editvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'Oauth2ClientEdit',
  props: {
    clientId: {
      type: String,
      required: true
    },
    initialSecret: {
      type: String,
      default: ''
    },
    scopes: {
      type: Object,
      required: true
    }
  },
  directives: {
    CopyToClipboard: external_CoreHome_["CopyToClipboard"]
  },
  emits: ['cancel', 'saved'],
  components: {
    ContentBlock: external_CoreHome_["ContentBlock"],
    Field: external_CorePluginsAdmin_["Field"],
    PasswordConfirmation: external_CorePluginsAdmin_["PasswordConfirmation"]
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
      loading: false,
      confirmRotateLabel: '',
      typeOptions,
      grantOptions,
      form: getDefaultForm(this.scopes),
      originalScope: '',
      visibleSecret: this.initialSecret,
      showPasswordConfirmModal: false,
      passwordConfirmAction: ''
    };
  },
  created() {
    this.init();
  },
  watch: {
    clientId() {
      this.init();
    },
    initialSecret(newSecret) {
      this.visibleSecret = newSecret;
    },
    'form.type': 'onFormTypeChange'
  },
  computed: {
    isEditMode() {
      return this.clientId !== '0';
    },
    contentTitle() {
      return this.isEditMode ? this.translate('OAuth2_AdminEditTitle') : this.translate('OAuth2_AdminCreateTitle');
    },
    submitLabel() {
      return this.isEditMode ? this.translate('OAuth2_AdminUpdate') : this.translate('OAuth2_AdminSave');
    },
    visibleGrantOptions() {
      if (this.form.type === 'public') {
        const filtered = {};
        if (this.grantOptions.authorization_code) {
          filtered.authorization_code = this.grantOptions.authorization_code;
        }
        if (this.grantOptions.refresh_token) {
          filtered.refresh_token = this.grantOptions.refresh_token;
        }
        return filtered;
      }
      return this.grantOptions;
    },
    showSecretPanel() {
      return this.form.type === 'confidential' && (this.isEditMode || !!this.visibleSecret);
    },
    canRegenerateSecret() {
      return this.isEditMode && this.form.type === 'confidential';
    },
    displayedSecret() {
      return this.visibleSecret || '*************';
    },
    secretInlineHelp() {
      if (this.visibleSecret) {
        return this.translate('OAuth2_ClientSecretVisibleHelp');
      }
      return this.translate('OAuth2_ClientSecretMaskedHelp');
    }
  },
  methods: {
    init() {
      this.removeNotifications();
      this.form = getDefaultForm(this.scopes);
      this.visibleSecret = this.initialSecret;
      if (!this.isEditMode) {
        return;
      }
      this.loading = true;
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'OAuth2.getClient',
        clientId: this.clientId
      }).then(client => {
        this.form = {
          name: client.name || '',
          description: client.description || '',
          type: client.type || 'confidential',
          grant_types: client.grant_types || [],
          scope: client.scopes && client.scopes[0] || Object.keys(this.scopes || {})[0] || '',
          redirect_uris: (client.redirect_uris || []).join('\n'),
          active: !!client.active
        };
        this.originalScope = this.form.scope;
      }).finally(() => {
        this.loading = false;
      });
    },
    onFormTypeChange(newType) {
      if (newType === 'public' && this.form.grant_types.includes('client_credentials')) {
        this.form.grant_types = this.form.grant_types.filter(value => value !== 'client_credentials');
      }
      if (newType === 'public') {
        this.visibleSecret = '';
      }
    },
    rotateSecret() {
      if (!this.canRegenerateSecret) {
        return;
      }
      this.confirmRotateLabel = this.translate('OAuth2_AdminRotateConfirm', this.form.name || this.clientId);
      this.passwordConfirmAction = 'rotate';
      this.showPasswordConfirmModal = true;
    },
    submit() {
      this.removeNotifications();
      if (!this.checkRequiredFieldsAreSet()) {
        return;
      }
      this.confirmRotateLabel = this.isEditMode ? this.translate('OAuth2_AdminUpdate') : this.translate('OAuth2_AdminCreateTitle');
      this.passwordConfirmAction = 'save';
      this.showPasswordConfirmModal = true;
    },
    onPasswordConfirmed(passwordConfirmation) {
      this.showPasswordConfirmModal = false;
      if (this.passwordConfirmAction === 'rotate') {
        this.doRotateSecret(passwordConfirmation);
      } else if (this.passwordConfirmAction === 'save') {
        this.saveClient(passwordConfirmation);
      }
      this.passwordConfirmAction = '';
    },
    doRotateSecret(passwordConfirmation) {
      this.loading = true;
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'OAuth2.rotateSecret',
        clientId: this.clientId
      }, {
        postParams: {
          passwordConfirmation
        }
      }).then(response => {
        if (response !== null && response !== void 0 && response.secret) {
          this.visibleSecret = response.secret;
          const code = `<code>${this.visibleSecret}</code>`;
          const message = `${this.translate('OAuth2_AdminRotatedNotification')}<br>${this.translate('OAuth2_ClientSecretDisplayedNotification', code)}`;
          this.showNotification(`<span class="success-msg-created">${message}</span>`, 'success', 'transient');
        }
      }).finally(() => {
        this.loading = false;
      });
    },
    scopeLevel(scope) {
      const levels = {
        'matomo:read': 1,
        'matomo:write': 2,
        'matomo:admin': 3,
        'matomo:superuser': 4
      };
      return levels[scope] || 0;
    },
    saveClient(passwordConfirmation) {
      this.loading = true;
      const scopeWasReduced = this.isEditMode && this.scopeLevel(this.form.scope) < this.scopeLevel(this.originalScope);
      const params = {
        method: this.isEditMode ? 'OAuth2.updateClient' : 'OAuth2.createClient',
        name: this.form.name.trim(),
        description: this.form.description,
        type: this.form.type,
        grantTypes: this.form.grant_types,
        scope: this.form.scope,
        redirectUris: this.form.redirect_uris,
        active: this.form.active ? '1' : '0'
      };
      if (this.isEditMode) {
        params.clientId = this.clientId;
      }
      external_CoreHome_["AjaxHelper"].fetch(params, {
        postParams: {
          passwordConfirmation
        }
      }).then(response => {
        this.visibleSecret = response.secret || '';
        const safeClientName = external_CoreHome_["Matomo"].helper.htmlEntities(response.client.name || '');
        const clientMessage = this.isEditMode ? this.translate('OAuth2_AdminUpdated', safeClientName) : this.translate('OAuth2_AdminCreated', safeClientName);
        const code = `<code>${this.visibleSecret}</code>`;
        const secretMessage = response.secret ? `${this.translate('OAuth2_ClientSecretHelp')}<br>${this.translate('OAuth2_ClientSecretDisplayedNotification', code)}` : '';
        const message = [clientMessage, secretMessage].filter(Boolean).join(' ');
        this.$emit('saved', {
          client: response.client,
          secret: response.secret || null
        });
        external_CoreHome_["MatomoUrl"].updateHash(Object.assign(Object.assign({}, external_CoreHome_["MatomoUrl"].hashParsed.value), {}, {
          idClient: response.client.client_id
        }));
        this.originalScope = this.form.scope;
        setTimeout(() => {
          this.showNotification(`<span class="success-msg-created">${message}</span>`, 'success', 'transient');
          if (scopeWasReduced) {
            this.showNotification(this.translate('OAuth2_AdminScopeReducedWarning'), 'warning', 'persistent', `${Editvue_type_script_lang_ts_notificationId}scopereduced`);
          }
        }, 50);
      }).finally(() => {
        this.loading = false;
      });
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
    removeNotifications() {
      external_CoreHome_["NotificationsStore"].remove(Editvue_type_script_lang_ts_notificationId);
      external_CoreHome_["NotificationsStore"].remove(`${Editvue_type_script_lang_ts_notificationId}scopereduced`);
      external_CoreHome_["NotificationsStore"].remove('ajaxHelper');
    },
    showNotification(message, context, type = null, id = Editvue_type_script_lang_ts_notificationId) {
      const notificationInstanceId = external_CoreHome_["NotificationsStore"].show({
        message,
        context,
        id,
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
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/Edit.vue?vue&type=script&lang=ts
 
// EXTERNAL MODULE: ./plugins/OAuth2/vue/src/OAuthClients/Edit.vue?vue&type=style&index=0&id=02b0c366&scoped=true&lang=css
var Editvue_type_style_index_0_id_02b0c366_scoped_true_lang_css = __webpack_require__("7a8a");

// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/Edit.vue





Editvue_type_script_lang_ts.render = Editvue_type_template_id_02b0c366_scoped_true_render
Editvue_type_script_lang_ts.__scopeId = "data-v-02b0c366"

/* harmony default export */ var Edit = (Editvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--15-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--15-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--1-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--1-1!./plugins/OAuth2/vue/src/OAuthClients/Manage.vue?vue&type=script&lang=ts




/* harmony default export */ var Managevue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  name: 'Oauth2AdminApp',
  props: {
    initialClients: {
      type: Array,
      required: true
    },
    scopes: {
      type: Object,
      required: true
    },
    authorizeUrl: {
      type: String,
      required: true
    },
    tokenUrl: {
      type: String,
      required: true
    }
  },
  components: {
    Oauth2ClientList: List,
    Oauth2ClientEdit: Edit
  },
  data() {
    return {
      clients: this.initialClients || [],
      secret: '',
      secretClientId: '',
      editedClientId: ''
    };
  },
  created() {
    Object(external_commonjs_vue_commonjs2_vue_root_Vue_["watch"])(() => external_CoreHome_["MatomoUrl"].hashParsed.value.idClient, idClient => {
      this.initState(idClient);
    });
    this.initState(external_CoreHome_["MatomoUrl"].hashParsed.value.idClient);
  },
  methods: {
    initState(idClient) {
      if (!idClient) {
        this.secret = '';
        this.secretClientId = '';
      } else if (this.secretClientId && this.secretClientId !== idClient) {
        this.secret = '';
        this.secretClientId = '';
      }
      this.editedClientId = idClient || '';
    },
    createClient() {
      external_CoreHome_["MatomoUrl"].updateHash(Object.assign(Object.assign({}, external_CoreHome_["MatomoUrl"].hashParsed.value), {}, {
        idClient: '0'
      }));
      this.secret = '';
      this.secretClientId = '';
    },
    editClient(clientId) {
      if (this.secretClientId !== clientId) {
        this.secret = '';
        this.secretClientId = '';
      }
      external_CoreHome_["MatomoUrl"].updateHash(Object.assign(Object.assign({}, external_CoreHome_["MatomoUrl"].hashParsed.value), {}, {
        idClient: clientId
      }));
    },
    showList() {
      const params = Object.assign({}, external_CoreHome_["MatomoUrl"].hashParsed.value);
      delete params.idClient;
      this.secret = '';
      this.secretClientId = '';
      external_CoreHome_["MatomoUrl"].updateHash(params);
    },
    onClientSaved(payload) {
      const index = this.clients.findIndex(client => client.client_id === payload.client.client_id);
      if (index === -1) {
        this.clients.push(payload.client);
      } else {
        this.clients.splice(index, 1, payload.client);
      }
      this.clients = [...this.clients].sort((left, right) => {
        const leftTime = left.updated_at ? new Date(left.updated_at).getTime() : 0;
        const rightTime = right.updated_at ? new Date(right.updated_at).getTime() : 0;
        if (rightTime !== leftTime) {
          return rightTime - leftTime;
        }
        return left.name.localeCompare(right.name);
      });
      this.secret = payload.secret || '';
      this.secretClientId = payload.secret ? payload.client.client_id : '';
    },
    onClientDeleted(clientId) {
      this.secret = '';
      if (this.secretClientId === clientId) {
        this.secretClientId = '';
      }
      this.clients = this.clients.filter(client => client.client_id !== clientId);
    },
    onClientUpdated(updatedClient) {
      const index = this.clients.findIndex(client => client.client_id === updatedClient.client_id);
      if (index === -1) {
        return;
      }
      this.clients.splice(index, 1, updatedClient);
      this.clients = [...this.clients];
    }
  },
  computed: {
    isEditMode() {
      return !!this.editedClientId;
    }
  }
}));
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/Manage.vue?vue&type=script&lang=ts
 
// CONCATENATED MODULE: ./plugins/OAuth2/vue/src/OAuthClients/Manage.vue



Managevue_type_script_lang_ts.render = render

/* harmony default export */ var Manage = (Managevue_type_script_lang_ts);
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