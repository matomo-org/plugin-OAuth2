<template>
  <div class="oauth2-admin oauth2-admin-edit">
    <PasswordConfirmation
      v-model="showPasswordConfirmModal"
      @confirmed="onPasswordConfirmed"
    >
    </PasswordConfirmation>
    <ContentBlock
      :content-title="contentTitle"
    >
      <p v-if="loading">
        <span class="loadingPiwik"><img src="plugins/Morpheus/images/loading-blue.gif" />
          {{ translate('General_LoadingData') }}</span>
      </p>
      <form
        v-else
        @submit.prevent="submit"
      >
        <div class="row">
          <Field
              uicontrol="text"
              name="name"
              v-model="form.name"
              :inline-help="translate('OAuth2_AdminNameHelp')"
              :title="translate('OAuth2_AdminName')"
          />
        </div>
        <div class="row">
          <Field
              uicontrol="textarea"
              name="description"
              v-model="form.description"
              :rows="1"
              :ui-control-attributes="{ style: 'min-height: auto;' }"
              :inline-help="translate('OAuth2_AdminDescriptionHelp')"
              :title="translate('OAuth2_AdminDescription')"
              :placeholder="translate('OAuth2_AdminDescriptionPlaceholder')"
          />
        </div>
        <div
            class="row"
            v-if="isEditMode"
        >
          <Field
              uicontrol="text"
              name="client_id"
              :model-value="clientId"
              :title="translate('OAuth2_AdminClientId')"
              :disabled="true"
          />
        </div>
        <div
          v-if="showSecretPanel"
          class="row oauth2-secret-head"
        >
          <label class="col s12">
            {{ translate('OAuth2_ClientSecret') }}
            <a
              v-if="canRegenerateSecret"
              @click.prevent="rotateSecret"
            >
              ({{ translate('OAuth2_AdminRotateSecret') }})
            </a>
          </label>
        </div>
        <div
          v-if="showSecretPanel"
          class="oauth2-secret-div form-group row matomo-form-field"
        >
          <div class="col s12 m6">
            <div class="copy-secret-wrapper-div">
              <pre
                v-if="visibleSecret"
                v-copy-to-clipboard="{}"
                class="client-secret-code"
              >{{ displayedSecret }}</pre>
              <pre
                v-else
                class="client-secret-code"
              >{{ displayedSecret }}</pre>
            </div>
          </div>
          <div class="col s12 m6">
            <div class="form-help" v-html="$sanitize(secretInlineHelp)" />
          </div>
        </div>
        <div class="row" name="type">
          <template v-if="!isEditMode">
            <Field
              uicontrol="select"
              name="type"
              v-model="form.type"
              :title="translate('OAuth2_AdminType')"
              :inline-help="translate('OAuth2_AdminTypeHelp', '<strong>', '</strong>')"
              :options="typeOptions"
            />
          </template>
          <template v-else>
            <Field
              uicontrol="text"
              name="type"
              :model-value="typeOptions[form.type] || form.type"
              :title="translate('OAuth2_AdminType')"
              :disabled="true"
            />
          </template>
        </div>
        <div
          class="row"
          name="grantType"
        >
          <Field
            uicontrol="checkbox"
            :options="visibleGrantOptions"
            var-type="array"
            name="grant_types"
            v-model="form.grant_types"
            :inline-help="translate('OAuth2_AdminGrantTypesHelp')"
            :title="translate('OAuth2_AdminClientGrants')"
          />
        </div>
        <div
          class="row"
          name="scopes"
        >
          <Field
            uicontrol="select"
            :options="scopes"
            name="scopes"
            v-model="form.scope"
            :inline-help="translate('OAuth2_AdminScopeHelp', '<strong>', '</strong>')"
            :title="translate('OAuth2_AdminScope')"
          />
        </div>
        <div class="row">
          <Field
            uicontrol="textarea"
            name="redirect_uris"
            v-model="form.redirect_uris"
            placeholder="https://example.com/callback"
            :inline-help="translate('OAuth2_AdminRedirectUrisHelp')"
            :title="translate('OAuth2_AdminRedirectUris')"
          />
        </div>
        <div class="row">
          <button
            type="submit"
            class="btn"
            :disabled="loading"
          >
            {{ submitLabel }}
          </button>
        </div>
        <div class="entityCancel">
          <a @click.prevent="$emit('cancel')">{{ translate('General_Cancel') }}</a>
        </div>
      </form>
    </ContentBlock>
  </div>
</template>

<style scoped>
.oauth2-secret-head {
  margin-bottom: 0;
}

.client-secret-code {
  word-break: break-word;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  margin: 0;
}
</style>

<script lang="ts">
import { defineComponent, PropType } from 'vue';
import {
  AjaxHelper,
  ContentBlock,
  MatomoUrl,
  Matomo,
  NotificationType,
  NotificationsStore,
  CopyToClipboard,
} from 'CoreHome';
import { Field, PasswordConfirmation } from 'CorePluginsAdmin';
import { Client, ClientForm } from '../types';

const notificationId = 'oauth2clientedit';

function getDefaultForm(scopes: Record<string, string>): ClientForm {
  const firstScope = Object.keys(scopes || {})[0] || '';

  return {
    name: '',
    description: '',
    type: 'confidential',
    grant_types: ['authorization_code', 'client_credentials', 'refresh_token'],
    scope: firstScope,
    redirect_uris: '',
    active: true,
  };
}

export default defineComponent({
  name: 'Oauth2ClientEdit',
  props: {
    clientId: {
      type: String,
      required: true,
    },
    initialSecret: {
      type: String,
      default: '',
    },
    scopes: {
      type: Object as PropType<Record<string, string>>,
      required: true,
    },
  },
  directives: {
    CopyToClipboard,
  },
  emits: ['cancel', 'saved'],
  components: {
    ContentBlock,
    Field,
    PasswordConfirmation,
  },
  data() {
    const typeOptions = {
      confidential: this.translate('OAuth2_AdminConfidential'),
      public: this.translate('OAuth2_AdminPublic'),
    };

    const grantOptions = {
      authorization_code: this.translate('OAuth2_AdminGrantAuthorizationCode'),
      client_credentials: this.translate('OAuth2_AdminGrantClientCredentials'),
      refresh_token: this.translate('OAuth2_AdminGrantRefreshToken'),
    };

    return {
      loading: false,
      confirmRotateLabel: '',
      typeOptions,
      grantOptions,
      form: getDefaultForm(this.scopes),
      visibleSecret: this.initialSecret,
      showPasswordConfirmModal: false,
      passwordConfirmAction: '',
    };
  },
  created() {
    this.init();
  },
  watch: {
    clientId() {
      this.init();
    },
    initialSecret(newSecret: string) {
      this.visibleSecret = newSecret;
    },
    'form.type': 'onFormTypeChange',
  },
  computed: {
    isEditMode(): boolean {
      return this.clientId !== '0';
    },
    contentTitle(): string {
      return this.isEditMode
        ? this.translate('OAuth2_AdminEditTitle')
        : this.translate('OAuth2_AdminCreateTitle');
    },
    submitLabel(): string {
      return this.isEditMode
        ? this.translate('OAuth2_AdminUpdate')
        : this.translate('OAuth2_AdminSave');
    },
    visibleGrantOptions(): Record<string, string> {
      if (this.form.type === 'public') {
        const filtered: Record<string, string> = {};
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
    showSecretPanel(): boolean {
      return this.form.type === 'confidential' && (this.isEditMode || !!this.visibleSecret);
    },
    canRegenerateSecret(): boolean {
      return this.isEditMode && this.form.type === 'confidential';
    },
    displayedSecret(): string {
      return this.visibleSecret || '*************';
    },
    secretInlineHelp(): string {
      if (this.visibleSecret) {
        return this.translate('OAuth2_ClientSecretVisibleHelp');
      }

      return this.translate('OAuth2_ClientSecretMaskedHelp');
    },
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
      AjaxHelper.fetch<Client>({
        method: 'OAuth2.getClient',
        clientId: this.clientId,
      }).then((client) => {
        this.form = {
          name: client.name || '',
          description: client.description || '',
          type: client.type || 'confidential',
          grant_types: client.grant_types || [],
          scope: (client.scopes && client.scopes[0]) || Object.keys(this.scopes || {})[0] || '',
          redirect_uris: (client.redirect_uris || []).join('\n'),
          active: !!client.active,
        };
      }).finally(() => {
        this.loading = false;
      });
    },
    onFormTypeChange(newType: string) {
      if (newType === 'public' && this.form.grant_types.includes('client_credentials')) {
        this.form.grant_types = this.form.grant_types.filter((value: string) => value !== 'client_credentials');
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

      this.confirmRotateLabel = this.isEditMode
        ? this.translate('OAuth2_AdminUpdate')
        : this.translate('OAuth2_AdminCreateTitle');
      this.passwordConfirmAction = 'save';
      this.showPasswordConfirmModal = true;
    },
    onPasswordConfirmed(passwordConfirmation: string) {
      this.showPasswordConfirmModal = false;
      if (this.passwordConfirmAction === 'rotate') {
        this.doRotateSecret(passwordConfirmation);
      } else if (this.passwordConfirmAction === 'save') {
        this.saveClient(passwordConfirmation);
      }
      this.passwordConfirmAction = '';
    },
    doRotateSecret(passwordConfirmation: string) {
      this.loading = true;
      AjaxHelper.fetch({
        method: 'OAuth2.rotateSecret',
        clientId: this.clientId,
      },
      {
        postParams: {
          passwordConfirmation,
        },
      }).then((response) => {
        if (response?.secret) {
          this.visibleSecret = response.secret;
          const code = `<code>${this.visibleSecret}</code>`;
          const message = `${this.translate('OAuth2_AdminRotatedNotification')}<br>${this.translate('OAuth2_ClientSecretDisplayedNotification', code)}`;
          this.showNotification(`<span class="success-msg-created">${message}</span>`, 'success', 'transient');
        }
      }).finally(() => {
        this.loading = false;
      });
    },
    saveClient(passwordConfirmation: string) {
      this.loading = true;
      const params = {
        method: this.isEditMode ? 'OAuth2.updateClient' : 'OAuth2.createClient',
        name: this.form.name.trim(),
        description: this.form.description,
        type: this.form.type,
        grantTypes: this.form.grant_types,
        scope: this.form.scope,
        redirectUris: this.form.redirect_uris,
        active: this.form.active ? '1' : '0',
      } as Record<string, string|string[]>;

      if (this.isEditMode) {
        params.clientId = this.clientId;
      }

      AjaxHelper.fetch(params, { postParams: { passwordConfirmation } }).then((response) => {
        this.visibleSecret = response.secret || '';
        const safeClientName = Matomo.helper.htmlEntities(response.client.name || '');
        const clientMessage = this.isEditMode
          ? this.translate('OAuth2_AdminUpdated', safeClientName)
          : this.translate('OAuth2_AdminCreated', safeClientName);
        const code = `<code>${this.visibleSecret}</code>`;
        const secretMessage = response.secret
          ? `${this.translate('OAuth2_ClientSecretHelp')}<br>${this.translate('OAuth2_ClientSecretDisplayedNotification', code)}`
          : '';
        const message = [clientMessage, secretMessage].filter(Boolean).join(' ');

        this.$emit('saved', {
          client: response.client,
          secret: response.secret || null,
        });

        MatomoUrl.updateHash({
          ...MatomoUrl.hashParsed.value,
          idClient: response.client.client_id,
        });

        setTimeout(() => {
          this.showNotification(`<span class="success-msg-created">${message}</span>`, 'success', 'transient');
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
      NotificationsStore.remove(notificationId);
      NotificationsStore.remove('ajaxHelper');
    },
    showNotification(message: string, context: NotificationType['context'],
      type: null|NotificationType['type'] = null) {
      const notificationInstanceId = NotificationsStore.show({
        message,
        context,
        id: notificationId,
        type: type !== null ? type : 'toast',
      });
      setTimeout(() => {
        NotificationsStore.scrollToNotification(notificationInstanceId);
      }, 200);
    },
    showErrorFieldNotProvidedNotification(title: string) {
      const message = this.translate('OAuth2_ErrorXNotProvided', [title]);
      this.showNotification(message, 'error');
    },
  },
});
</script>
