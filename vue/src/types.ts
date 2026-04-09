export type Client = {
  client_id: string;
  name: string;
  description?: string;
  type: string;
  grant_types: string[];
  scopes: string[];
  redirect_uris: string[];
  active: boolean;
  owner_login?: string;
  created_at?: string;
  updated_at?: string;
  last_used_at?: string|null;
  secret?: string|null;
  isLoading?: boolean|false;
};

export type ClientForm = {
  name: string;
  description: string;
  type: string;
  grant_types: string[];
  scope: string;
  redirect_uris: string;
  active: boolean;
};
