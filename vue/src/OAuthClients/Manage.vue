<template>
  <div class="oauth2-admin" v-cloak>
    <Oauth2ClientList
      v-if="!isEditMode"
      :clients="clients"
      :scopes="scopes"
      :authorize-url="authorizeUrl"
      :token-url="tokenUrl"
      @create="createClient"
      @edit="editClient"
      @deleted="onClientDeleted"
      @updated="onClientUpdated"
    />
    <Oauth2ClientEdit
      v-else
      :client-id="editedClientId"
      :scopes="scopes"
      :initial-secret="secret"
      @cancel="showList"
      @saved="onClientSaved"
    />
  </div>
</template>

<script lang="ts">
import { defineComponent, watch } from 'vue';
import { MatomoUrl } from 'CoreHome';
import Oauth2ClientList from './List.vue';
import Oauth2ClientEdit from './Edit.vue';
import { Client } from '../types';

export default defineComponent({
  name: 'Oauth2AdminApp',
  props: {
    initialClients: {
      type: Array as () => Client[],
      required: true,
    },
    scopes: {
      type: Object as () => Record<string, string>,
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
  components: {
    Oauth2ClientList,
    Oauth2ClientEdit,
  },
  data() {
    return {
      clients: this.initialClients as Client[] || [],
      secret: '',
      secretClientId: '',
      editedClientId: '',
    };
  },
  created() {
    watch(() => MatomoUrl.hashParsed.value.idClient as string, (idClient) => {
      this.initState(idClient);
    });

    this.initState(MatomoUrl.hashParsed.value.idClient as string);
  },
  methods: {
    initState(idClient?: string) {
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
      MatomoUrl.updateHash({
        ...MatomoUrl.hashParsed.value,
        idClient: '0',
      });
      this.secret = '';
      this.secretClientId = '';
    },
    editClient(clientId: string) {
      if (this.secretClientId !== clientId) {
        this.secret = '';
        this.secretClientId = '';
      }

      MatomoUrl.updateHash({
        ...MatomoUrl.hashParsed.value,
        idClient: clientId,
      });
    },
    showList() {
      const params = {
        ...MatomoUrl.hashParsed.value,
      };
      delete params.idClient;
      this.secret = '';
      this.secretClientId = '';
      MatomoUrl.updateHash(params);
    },
    onClientSaved(payload: { client: Client; secret: string|null }) {
      const index = this.clients.findIndex(
        (client) => client.client_id === payload.client.client_id,
      );
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
    onClientDeleted(clientId: string) {
      this.secret = '';
      if (this.secretClientId === clientId) {
        this.secretClientId = '';
      }
      this.clients = this.clients.filter((client) => client.client_id !== clientId);
    },
    onClientUpdated(updatedClient: Client) {
      const index = this.clients.findIndex(
        (client) => client.client_id === updatedClient.client_id,
      );
      if (index === -1) {
        return;
      }

      this.clients.splice(index, 1, updatedClient);
      this.clients = [...this.clients];
    },
  },
  computed: {
    isEditMode() {
      return !!this.editedClientId;
    },
  },
});
</script>
