# OAuth 2.0 Plugin for Matomo

[![Build Status](https://github.com/matomo-org/plugin-OAuth2/actions/workflows/matomo-tests.yml/badge.svg)](https://github.com/matomo-org/plugin-OAuth2/actions/workflows/matomo-tests.yml)

## Description

This plugin adds a **first-party OAuth 2.0 Authorization Server** to Matomo, allowing external applications to securely access Matomo APIs using OAuth2 access tokens instead of `token_auth`.

It supports standard OAuth 2.0 flows including **Authorization Code (PKCE)**, **Client Credentials**, and **Refresh Token**.

The OAuth 2.0 plugin replaces static authentication with a token-based flow tied to your existing login system. Each application requests permission, receives scoped access, and operates within defined limits. No need to distribute or manage long-lived credentials across tools and services.

Tokens expire by default, can be refreshed when needed, and revoked instantly without affecting other integrations. This reduces exposure and simplifies access management.

For teams running multiple integrations, OAuth 2.0 is the practical choice for secure, maintainable access to Matomo data. Every connection is authorised, bounded, and straightforward to control.

## Features

- OAuth 2.0 Authorization Server integrated with Matomo
- Manage OAuth clients via **Administration → Platform → OAuth 2.0** (For Matomo Cloud it will be **Administration → Export → OAuth 2.0**)
- Supported grant types:
  - Authorization Code (with PKCE)
  - Client Credentials
  - Refresh Token
- OAuth scopes:
  - `matomo:read`
  - `matomo:write`
  - `matomo:admin`
  - `matomo:superuser`
- RSA signing keys for JWT tokens
- Built using **league/oauth2-server**
- Bearer token authentication for Matomo APIs
- Client management UI with create, edit, pause/resume, delete, and secret rotation for confidential clients

## OAuth Endpoints

| Endpoint | Description |
| --- | --- |
| `/index.php?module=OAuth2&action=authorize` | Authorization endpoint |
| `/index.php?module=OAuth2&action=token` | Token endpoint |

Optional cleaner routes can be added:

```
/oauth2/authorize
/oauth2/token
```

## Authorization Server Metadata (Discovery)

The plugin exposes an [RFC 8414](https://datatracker.ietf.org/doc/html/rfc8414) **OAuth 2.0 Authorization Server Metadata** document so clients can discover the endpoints and capabilities automatically:

```
/.well-known/oauth-authorization-server
```

It returns JSON describing the issuer, authorization/token endpoints, supported scopes, grant types, response types, PKCE methods, and token endpoint authentication methods. The contents reflect your current OAuth 2.0 system settings (e.g. disabled grant types are omitted).

Example response:

```json
{
  "issuer": "https://matomo.example.com",
  "authorization_endpoint": "https://matomo.example.com/index.php?module=OAuth2&action=authorize",
  "token_endpoint": "https://matomo.example.com/index.php?module=OAuth2&action=token",
  "scopes_supported": ["matomo:read", "matomo:write", "matomo:admin"],
  "response_types_supported": ["code"],
  "code_challenge_methods_supported": ["S256", "plain"],
  "grant_types_supported": ["authorization_code", "client_credentials", "refresh_token"],
  "token_endpoint_auth_methods_supported": ["client_secret_basic", "client_secret_post"]
}
```

### Extending the metadata

Other plugins (for example an MCP or AI integration) can add or override fields in the discovery document by listening to the `OAuth2.authorizationServerMetadata` event. It is fired after the base document is built and receives the metadata array by reference:

```php
public function registerEvents()
{
    return [
        'OAuth2.authorizationServerMetadata' => 'extendAuthorizationServerMetadata',
    ];
}

public function extendAuthorizationServerMetadata(array &$metadata)
{
    $metadata['registration_endpoint'] = 'https://matomo.example.com/index.php?module=MyPlugin&action=register';
}
```

This is the recommended way to contribute optional RFC 8414 fields such as `registration_endpoint`, `introspection_endpoint`, `revocation_endpoint`, or `jwks_uri`.

### Web server routing (required)

The discovery path lives at the site root, so your web server must forward it to the plugin's metadata action (`index.php?module=OAuth2&action=metadata`). Use an **internal rewrite** (not a redirect), matching the **exact path with no trailing slash**.

**Apache** (`.htaccess` in the Matomo root, or the vhost):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^\.well-known/oauth-authorization-server$ index.php?module=OAuth2&action=metadata [L,QSA]
</IfModule>
```

**Nginx** (inside the Matomo `server { ... }` block):

```nginx
location = /.well-known/oauth-authorization-server {
    rewrite ^ /index.php?module=OAuth2&action=metadata last;
}
```

In both cases the rewrite is internal and preserves the original `REQUEST_URI`, which the plugin uses to derive the `issuer`. Do not use a redirect (`return 301` / `rewrite ... permanent`), as that would change the path and break discovery.

**DDEV** uses nginx-fpm by default. Add a config snippet so DDEV includes it inside the site's `server { ... }` block — create `.ddev/nginx/oauth2-well-known.conf`:

```nginx
location = /.well-known/oauth-authorization-server {
    rewrite ^ /index.php?module=OAuth2&action=metadata last;
}
```

Then run `ddev restart` to apply it. (If your project uses the `apache-fpm` webserver type instead, put the Apache `RewriteRule` above into `.ddev/apache/apache-site.conf` and restart.)

Notes:

- **Subdirectory installs:** the `issuer` is the Matomo base URL including the subdirectory (e.g. `https://example.com/matomo`). Per [RFC 8414 §3.1](https://datatracker.ietf.org/doc/html/rfc8414#section-3.1), the well-known string is inserted between the host and the issuer's path component, so the canonical discovery URL is `https://example.com/.well-known/oauth-authorization-server/matomo`. Note this lives at the **domain root**, not under the Matomo directory, so the rewrite must go in the root server config and route to the subdirectory's front controller, e.g. Apache:

    ```apache
    RewriteRule ^\.well-known/oauth-authorization-server/matomo$ /matomo/index.php?module=OAuth2&action=metadata [L,QSA]
    ```

    The plugin derives the `issuer` correctly from either layout, so it also accepts the appended form `https://example.com/matomo/.well-known/oauth-authorization-server` — handy when you can only edit Matomo's own `.htaccess` — but the RFC 8414 form above is what spec-compliant clients request.
- Make sure no physical `.well-known/oauth-authorization-server` file or directory exists in the Matomo root, or the web server will serve it directly instead of routing to Matomo.

## Setup

### 1. Create an OAuth Client

Navigate to:

```
Administration → Platform → OAuth 2.0 (On-premise)
Administration → Export → OAuth 2.0 (Matomo Cloud)
```

Create a client and configure:

- Client type
  - **Confidential** (requires client secret)
  - **Public** (no secret)
- Allowed grant types
- Allowed scopes
- Redirect URI (required for Authorization Code flow)

From the client list, you can pause or resume a client, open it for editing, or delete it. For confidential clients, secret rotation is available from the edit screen.

Client secrets are shown in full only when a confidential client is created or when its secret is rotated. After that, the secret is masked and must be rotated again if you need a new value.

Example client:

```
Client ID: analytics_app
Client Secret: 7fa9c0f81b8b4a12
Redirect URI: https://example-app.com/oauth/callback
```

## OAuth Flow Overview

1. Client redirects the user to `/authorize`
2. User logs into Matomo and approves access
3. Matomo redirects back with an authorization `code`
4. Client exchanges the `code` for an access token at `/token`
5. Client calls Matomo APIs using

```
Authorization: Bearer ACCESS_TOKEN
```

## Authorization Code Flow

### Step 1: Generate PKCE Parameters (Public Clients)

PKCE requires two values:

- `code_verifier`
- `code_challenge`

Example:

```
code_verifier = dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk
code_challenge = E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM
```

Where:

```
code_challenge = BASE64URL(SHA256(code_verifier))
```

### Step 2: Redirect User to Authorization Endpoint

#### PKCE Example (Public Client)

```
https://matomo.example.com/index.php?module=OAuth2&action=authorize
&response_type=code
&client_id=analytics_app
&redirect_uri=https://example-app.com/oauth/callback
&scope=matomo:read
&state=abc123
&code_challenge=E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM
&code_challenge_method=S256
```

#### Confidential Client Example

```
https://matomo.example.com/index.php?module=OAuth2&action=authorize
&response_type=code
&client_id=analytics_app
&redirect_uri=https://example-app.com/oauth/callback
&scope=matomo:read
&state=abc123
```

The user will:

1. Log in to Matomo
2. Review requested permissions (if the request lists multiple space-separated scopes, pick exactly one to grant — the least privileged scope is preselected)
3. Click **Allow**

Matomo will redirect back:

```
https://example-app.com/oauth/callback?code=AUTHORIZATION_CODE&state=abc123
```

## Exchange Authorization Code for Tokens

**Note:** The authorize request may list multiple space-separated scopes (e.g. `scope=matomo:read matomo:write`). The user grants exactly one of them on the consent screen, and the issued tokens always carry that single scope.

### PKCE Token Request

```
curl -X POST 'https://matomo.example.com/index.php?module=OAuth2&action=token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=authorization_code' \
  -d 'client_id=analytics_app' \
  -d 'redirect_uri=https://example-app.com/oauth/callback' \
  -d 'code=AUTHORIZATION_CODE' \
  -d 'code_verifier=dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'
```

### Confidential Client Token Request

```
curl -X POST 'https://matomo.example.com/index.php?module=OAuth2&action=token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=authorization_code' \
  -d 'client_id=analytics_app' \
  -d 'client_secret=7fa9c0f81b8b4a12' \
  -d 'redirect_uri=https://example-app.com/oauth/callback' \
  -d 'code=AUTHORIZATION_CODE'
```

## Client Credentials Flow (Server-to-Server)

The **Client Credentials grant** is used when a backend service needs to access Matomo APIs **without user interaction**.

Typical use cases:

- Internal analytics dashboards
- Scheduled data exports
- Backend integrations

### Token Request

```
curl -X POST 'https://matomo.example.com/index.php?module=OAuth2&action=token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=client_credentials' \
  -d 'client_id=analytics_app' \
  -d 'client_secret=7fa9c0f81b8b4a12' \
  -d 'scope=matomo:read'
```

## Example Token Response

```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "def50200a1b9"
}
```

## Refresh Token

Use a refresh token to obtain a new access token.

### Public Client (PKCE)

```
curl -X POST 'https://matomo.example.com/index.php?module=OAuth2&action=token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=refresh_token' \
  -d 'client_id=analytics_app' \
  -d 'refresh_token=def50200a1b9'
```

### Confidential Client

```
curl -X POST 'https://matomo.example.com/index.php?module=OAuth2&action=token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=refresh_token' \
  -d 'client_id=analytics_app' \
  -d 'client_secret=7fa9c0f81b8b4a12' \
  -d 'refresh_token=def50200a1b9'
```

## Calling Matomo APIs with OAuth 2.0

Once an access token is obtained, call Matomo APIs using the **Bearer token**.

Example:

```
curl https://matomo.example.com/index.php \
  -H "Authorization: Bearer ACCESS_TOKEN" \
  -d "module=API" \
  -d "method=VisitsSummary.get" \
  -d "idSite=1" \
  -d "period=day" \
  -d "date=today" \
  -d "format=json"
```

Example response:

```json
{
  "nb_visits": 124,
  "nb_actions": 210
}
```

## Security Notes

- Never expose **client secrets** in frontend applications.
- Public clients **must use PKCE**.
- Always use **HTTPS**.
- Store access tokens securely.
- Rotate client secrets periodically.
