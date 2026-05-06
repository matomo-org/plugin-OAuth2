<template>
  <div class="oauth2-admin oauth2-admin-list">
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
    </div>
    <div
      class="ui-confirm"
      ref="confirmToggleClient"
    >
      <h2>{{ confirmToggleLabel }} </h2>
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
      <p v-html="$sanitize(adminClientsDescription)" />
      <table
        v-content-table
      >
        <thead>
          <tr>
            <th>{{ translate('OAuth2_AdminName') }}</th>
            <th>{{ translate('OAuth2_AdminClientType') }}</th>
            <th>{{ translate('OAuth2_AdminClientGrants') }}</th>
            <th>{{ translate('OAuth2_AdminScope') }}</th>
            <th>{{ translate('OAuth2_AdminClientStatus') }}</th>
            <th>{{ translate('OAuth2_AdminClientId') }}</th>
            <th>{{ translate('OAuth2_AdminClientRedirects') }}</th>
            <th>{{ translate('OAuth2_AdminClientCreatedAt') }}</th>
            <th style="width: 180px;">{{ translate('OAuth2_AdminClientActions') }}</th>
          </tr>
        </thead>
        <tbody v-if="clients.length">
          <tr
            v-for="client in clients"
            :key="client.client_id"
          >
            <td :title="$sanitize(client.description)">
              {{ client.name }}
            </td>
            <td>{{ typeOptions[client.type] }}</td>
            <td>
              <div
                v-for="grantType in (client.grant_types || [])"
                :key="grantType"
              >
                {{ getGrantTypeLabel(grantType) }}
              </div>
            </td>
            <td>{{ getScopeLabel(client) }}</td>
            <td>
              <span>
                {{
                  client.active
                    ? translate('OAuth2_AdminActive')
                    : translate('OAuth2_AdminDisabled')
                }}
              </span>
            </td>
            <td>
              <code class="client-id-code">{{ client.client_id }}</code>
            </td>
            <td>
              <div
                v-for="uri in (client.redirect_uris || [])"
                :key="uri"
              >
                <code class="redirect-uri">{{ uri }}</code>
              </div>
            </td>
            <td class="created-at">{{ client.created_at }}</td>
            <td>
              <button
                :class="`table-action ${client.active ? 'icon-pause' : 'icon-play'}`"
                @click.prevent="toggleClientStatus(client)"
                :title="
                  client.active
                    ? translate('OAuth2_AdminPause')
                    : translate('OAuth2_AdminResume')
                "
              />
              <button
                class="table-action icon-edit"
                @click.prevent="$emit('edit', client.client_id)"
                :title="translate('OAuth2_AdminEdit')"
              />
              <button
                class="table-action icon-delete"
                @click.prevent="deleteClient(client)"
                :title="translate('OAuth2_AdminDelete')"
              />
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td colspan="9">{{ translate('OAuth2_AdminNoClients') }}</td>
          </tr>
        </tbody>
      </table>
      <div class="tableActionBar">
        <a
          class="createNewClient"
          @click.prevent="$emit('create')"
        ><span class="icon-add" /> {{ translate('OAuth2_AdminCreateTitle') }}</a>
      </div>
    </ContentBlock>
  </div>
</template>

<style scoped>
.client-id-code {
  max-width: 125px;
  display: inline-block;
  word-wrap: break-word;
}
.redirect-uri {
  max-width: 250px;
  display: inline-block;
  word-wrap: break-word;
}
</style>

<script lang="ts">
import { defineComponent, PropType } from 'vue';
import {
  AjaxHelper,
  ContentBlock,
  ContentTable,
  Matomo,
  NotificationType,
  NotificationsStore,
} from 'CoreHome';
import { Client } from '../types';

const notificationId = 'oauth2clientlist';

export default defineComponent({
  name: 'Oauth2ClientList',
  props: {
    clients: {
      type: Array as PropType<Client[]>,
      required: true,
    },
    scopes: {
      type: Object as PropType<Record<string, string>>,
      required: true,
    },
    authorizeUrl: {
      type: String,
      required: true,
    },
    tokenUrl: {
      type: String,
      required: true,
    },
  },
  emits: ['create', 'edit', 'deleted', 'updated'],
  components: {
    ContentBlock,
  },
  directives: {
    ContentTable,
  },
  data() {
    return {
      confirmDeleteLabel: '',
      confirmToggleLabel: '',
      typeOptions: {
        confidential: this.translate('OAuth2_AdminConfidential'),
        public: this.translate('OAuth2_AdminPublic'),
      } as Record<string, string>,
      grantTypeOptions: {
        authorization_code: this.translate('OAuth2_AdminGrantAuthorizationCode'),
        client_credentials: this.translate('OAuth2_AdminGrantClientCredentials'),
        refresh_token: this.translate('OAuth2_AdminGrantRefreshToken'),
      } as Record<string, string>,
    };
  },
  computed: {
    adminClientsDescription(): string {
      const authorizeUrl = `<a href="${this.$sanitize(this.authorizeUrl)}"><code>${this.$sanitize(this.authorizeUrl)}</code></a>`;
      const tokenUrl = `<a href="${this.$sanitize(this.tokenUrl)}"><code>${this.$sanitize(this.tokenUrl)}</code></a>`;

      return `${this.translate('OAuth2_AdminClientsDescriptions')} ${this.translate('OAuth2_AdminClientDescriptionAdditionalHelpText', authorizeUrl, tokenUrl)}`;
    },
  },
  methods: {
    getShortScopeLabel(scope: string) {
      const shortScopeLabels = {
        'matomo:read': this.translate('UsersManager_PrivView'),
        'matomo:write': this.translate('UsersManager_PrivWrite'),
        'matomo:admin': this.translate('UsersManager_PrivAdmin'),
        'matomo:superuser': this.translate('OAuth2_ScopeSuperUserShort'),
      } as Record<string, string>;

      return shortScopeLabels[scope] || this.scopes[scope] || scope;
    },
    showNotification(message: string, context: NotificationType['context'],
      type: null|NotificationType['type'] = null) {
      const instanceId = NotificationsStore.show({
        message,
        context,
        id: notificationId,
        type: type !== null ? type : 'toast',
      });

      setTimeout(() => {
        NotificationsStore.scrollToNotification(instanceId);
      }, 200);
    },
    removeNotifications() {
      NotificationsStore.remove(notificationId);
      NotificationsStore.remove('ajaxHelper');
    },
    getScopeLabel(client: Client) {
      const scope = client.scopes?.[0];

      if (!scope) {
        return '';
      }

      return this.getShortScopeLabel(scope);
    },
    getGrantTypeLabel(grantType: string) {
      return this.grantTypeOptions[grantType] || grantType;
    },
    toggleClientStatus(client: Client) {
      const safeClientName = Matomo.helper.htmlEntities(client.name || client.client_id);
      this.confirmToggleLabel = client.active
        ? this.translate('OAuth2_AdminPauseConfirm', safeClientName)
        : this.translate('OAuth2_AdminResumeConfirm', safeClientName);

      Matomo.helper.modalConfirm(this.$refs.confirmToggleClient as HTMLElement, {
        yes: () => {
          AjaxHelper.fetch({
            method: 'OAuth2.setClientActive',
            clientId: client.client_id,
            active: client.active ? '0' : '1',
          }).then((response) => {
            if (response?.client) {
              this.removeNotifications();
              const safeUpdatedClientName = Matomo.helper.htmlEntities(
                response.client.name || response.client.client_id,
              );
              this.showNotification(
                response.client.active
                  ? this.translate('OAuth2_AdminResumed', safeUpdatedClientName)
                  : this.translate('OAuth2_AdminPaused', safeUpdatedClientName),
                'success',
              );
              this.$emit('updated', response.client);
            }
          });
        },
      });
    },
    deleteClient(client: Client) {
      const safeClientName = Matomo.helper.htmlEntities(client.name || client.client_id);
      this.confirmDeleteLabel = this.translate('OAuth2_AdminDeleteConfirm', safeClientName);

      Matomo.helper.modalConfirm(this.$refs.confirmDeleteClient as HTMLElement, {
        yes: () => {
          AjaxHelper.fetch({
            method: 'OAuth2.deleteClient',
            clientId: client.client_id,
          }).then((response) => {
            if (response?.deleted) {
              this.removeNotifications();
              this.showNotification(this.translate('OAuth2_AdminDeleted', safeClientName), 'success');
              this.$emit('deleted', client.client_id);
            }
          });
        },
      });
    },
  },
});
</script>
