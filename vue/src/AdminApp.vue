<template>
  <div class="oauth2-admin" v-cloak>
    <div v-if="secret" class="alert alert-warning">
      <strong>Client secret:</strong> <code>{{ secret }}</code>
      <div class="form-help">Copy now; it will not be shown again.</div>
    </div>
    <div
      class="ui-confirm"
      ref="confirmDeleteClient"
    >
      <h2>{{ confirmDeleteLabel }} </h2>
      <input
        role="yes"
        type="button"
        :value="translate('General_Yes')"
      />
      <input
        role="no"
        type="button"
        :value="translate('General_No')"
      />
    </div>    <div
      class="ui-confirm"
      ref="confirmRotateClient"
    >
      <h2>{{ confirmRotateLabel }} </h2>
      <input
        role="yes"
        type="button"
        :value="translate('General_Yes')"
      />
      <input
        role="no"
        type="button"
        :value="translate('General_No')"
      />
    </div>
    <ContentBlock
      :content-title="translate('OAuth2_AdminHeading')"
      :feature="translate('OAuth2_AdminHeading')"
    >
      <p>{{ translate('OAuth2_AdminClientsDescriptions') }}</p>
      <table class="card card-table entityTable" v-if="clients && clients.length">
        <thead>
        <tr>
          <th>{{ translate('OAuth2_AdminName') }}</th>
          <th>{{ translate('OAuth2_AdminClientId') }}</th>
          <th>{{ translate('OAuth2_AdminClientCreatedAt') }}</th>
          <th>{{ translate('OAuth2_AdminClientType') }}</th>
          <th>{{ translate('OAuth2_AdminClientGrants') }}</th>
          <th>{{ translate('OAuth2_AdminClientRedirects') }}</th>
          <th>{{ translate('OAuth2_AdminClientActions') }}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="client in clients" :key="client.client_id">
          <td :title="client.description">
            <strong>{{ client.name }}</strong>
          </td>
          <td><code>{{ client.client_id }}</code></td>
          <td >{{ client.created_at }}</td>
          <td>{{ type_options[client.type] }}</td>
          <td>{{ (client.grant_types || []).join(', ') }}</td>
          <td>
            <div v-for="uri in (client.redirect_uris || [])" :key="uri"><code>{{ uri }}</code></div>
          </td>
          <td>
            <button class="table-action icon-refresh" @click.prevent="rotateSecret(client)"
                    :title="translate('OAuth2_AdminRotateSecret')"></button>
            <button class="table-action icon-delete" @click.prevent="deleteClient(client)"
                    :title="translate('OAuth2_AdminDelete')"></button>
          </td>
        </tr>
        </tbody>
      </table>
      <div v-else>{{ translate('OAuth2_AdminNoClients') }}</div>
    </ContentBlock>
    <ContentBlock
      :content-title="translate('OAuth2_AdminCreateTitle')"
    >
      <form @submit.prevent="createClient">
          <div class="row">
            <Field
              uicontrol="text" name="name" v-model="form.name"
              :inline-help="translate('OAuth2_AdminNameHelp')"
              :title="translate('OAuth2_AdminName')"/>
          </div>
          <div class="row">
            <Field
              uicontrol="textarea" name="description" v-model="form.description"
              :inline-help="translate('OAuth2_AdminDescriptionHelp')"
              :title="translate('OAuth2_AdminDescription')"/>
          </div>
          <div class="row">
            <Field
              uicontrol="select"
              name="type"
              v-model="form.type"
              :title="translate('OAuth2_AdminType')"
              :inline-help="translate('OAuth2_AdminTypeHelp', '<strong>', '</strong>')"
              :options="{confidential: translate('OAuth2_AdminConfidential'),
              public:translate('OAuth2_AdminPublic')}"
            />
          </div>
          <div class="row">
            <Field
              uicontrol="checkbox" :options="getGrantOptions" var-type="array"
              name="grant_types" v-model="form.grant_types"
              :inline-help="translate('OAuth2_AdminGrantTypesHelp')"
              :title="translate('OAuth2_AdminClientGrants')"/>
          </div>
          <div class="row">
            <Field
              uicontrol="select" :options="scopes"
              name="scopes" v-model="form.scope"
              :inline-help="translate('OAuth2_AdminScopeHelp', '<strong>', '</strong>')"
              :title="translate('OAuth2_AdminScope')"/>
          </div>
          <div class="row">
            <Field
              uicontrol="textarea" name="redirect_uris" v-model="form.redirect_uris"  placeholder="https://example.com/callback"
              :inline-help="translate('OAuth2_AdminRedirectUrisHelp')"
              :title="translate('OAuth2_AdminRedirectUris')"/>
          </div>
          <div class="row">
            <button type="submit" class="btn" :disabled="loading">
              {{ translate('OAuth2_AdminSave') }}
            </button>
          </div>
        </form>

    </ContentBlock>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { Field } from 'CorePluginsAdmin';
import {
  Matomo,
  ContentBlock,
  AjaxHelper,
  NotificationsStore,
  NotificationType,
} from 'CoreHome';

type Client = {
  client_id: string;
  name: string;
  description?: string;
  type: string;
  grant_types: string[];
  redirect_uris: string[];
  active: boolean;
};

const notificationId = 'oauth2clientcreate';

export default defineComponent({
  name: 'Oauth2AdminApp',
  props: {
    initialClients: {
      type: Array,
      required: true,
    },
    scopes: {
      type: Object,
      required: true,
    },
  },
  components: {
    Field,
    ContentBlock,
  },
  data() {
    const typeOptions = {
      confidential: this.translate('OAuth2_AdminConfidential'),
      public: this.translate('OAuth2_AdminPublic'),
    };
    type GrantOptions = {
      authorization_code: string;
      client_credentials?: string;
      refresh_token: string;
    };

    const grantOptions: GrantOptions = {
      authorization_code: this.translate('OAuth2_AdminGrantAuthorizationCode'),
      client_credentials: this.translate('OAuth2_AdminGrantClientCredentials'),
      refresh_token: this.translate('OAuth2_AdminGrantRefreshToken'),
    };

    return {
      clients: (this.initialClients as Client[]) || [],
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
        active: true,
      },
    };
  },
  computed: {
    visibleGrantOptions(): Record<string, string> {
      if (this.form.type === 'public') {
        const filtered: Record<string, string> = {};
        if (this.grant_options.authorization_code) {
          filtered.authorization_code = this.grant_options.authorization_code;
        }
        if (this.grant_options.refresh_token) {
          filtered.refresh_token = this.grant_options.refresh_token;
        }
        return filtered;
      }

      return this.grant_options;
    },
    getGrantOptions() {
      const grantOptions = this.grant_options;
      if (this.form.type === 'public') {
        delete grantOptions.client_credentials;
      }
      return grantOptions;
    },
  },
  watch: {
    'form.type': 'onFormTypeChange',
  },
  methods: {
    onFormTypeChange(newType: string) {
      if (newType === 'public' && this.form.grant_types.includes('client_credentials')) {
        this.form.grant_types = this.form.grant_types.filter((value: string) => value !== 'client_credentials');
      }
    },
    showSuccessNotification(method: string, message: string) {
      const instanceId = NotificationsStore.show({
        id: `OAuth2_${method}`,
        type: 'transient',
        context: 'success',
        message,
      });

      setTimeout(() => {
        NotificationsStore.scrollToNotification(instanceId);
      });
    },
    async fetchClients() {
      this.loading = true;
      try {
        await AjaxHelper.fetch({
          method: 'OAuth2.getClients',
          filter_limit: '-1',
        }).then((clients) => {
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
        active: 1,
      };
      try {
        await AjaxHelper.fetch(params).then((response) => {
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
    async rotateSecret(client: Client) {
      if (!client) {
        return;
      }

      this.confirmRotateLabel = this.translate('OAuth2_AdminRotateConfirm', client?.name || client?.client_id);

      Matomo.helper.modalConfirm(this.$refs.confirmRotateClient as HTMLElement, {
        yes: () => {
          this.loading = true;
          try {
            AjaxHelper.fetch({
              method: 'OAuth2.rotateSecret',
              clientId: client.client_id,
            }).then((response) => {
              if (response && response.secret) {
                this.secret = response.secret;

                const message = this.translate('OAuth2_AdminRotated', client.client_id);
                this.showSuccessNotification('rotateSecret', message);
              }
            });
          } finally {
            this.loading = false;
          }
        },
      });
    },
    async deleteClient(client: Client) {
      if (!client) {
        return;
      }

      this.confirmDeleteLabel = this.translate('OAuth2_AdminDeleteConfirm', client?.name || client?.client_id);

      Matomo.helper.modalConfirm(this.$refs.confirmDeleteClient as HTMLElement, {
        yes: () => {
          this.loading = true;
          try {
            AjaxHelper.fetch({
              method: 'OAuth2.deleteClient',
              clientId: client.client_id,
            }).then((response) => {
              if (response.deleted) {
                this.clients = this.clients.filter((c) => c.client_id !== client.client_id);

                const message = this.translate('OAuth2_AdminDeleted', client.client_id);
                this.showSuccessNotification('deleteClient', message);
              }
            });
          } finally {
            this.loading = false;
          }
        },
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
      if (!this.form.name) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminName');
      } else if (!this.form.type) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminType');
      } else if (!this.form.grant_types.length) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminClientGrants');
      } else if (!this.form.scope) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminScope');
      } else if (!this.form.redirect_uris) {
        response = false;
        errorMessage = this.translate('OAuth2_AdminRedirectUris');
      }
      if (!response && errorMessage) {
        this.showErrorFieldNotProvidedNotification(errorMessage);
      }

      return response;
    },
    removeAnyClientNotification() {
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
