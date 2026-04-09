# OAuth 2.0 Plugin for Matomo

This plugin adds a **first-party OAuth 2.0 Authorization Server** to Matomo, allowing external applications to securely access Matomo APIs using OAuth2 access tokens instead of `token_auth`.

It supports standard OAuth 2.0 flows including **Authorization Code (PKCE)**, **Client Credentials**, and **Refresh Token**.

[![Build Status](https://github.com/matomo-org/plugin-OAuth2/actions/workflows/matomo-tests.yml/badge.svg)](https://github.com/matomo-org/plugin-OAuth2/actions/workflows/matomo-tests.yml)

## Description

The OAuth 2.0 plugin replaces static authentication with a token-based flow tied to your existing login system. Each application requests permission, receives scoped access, and operates within defined limits. No need to distribute or manage long-lived credentials across tools and services.

Tokens expire by default, can be refreshed when needed, and revoked instantly without affecting other integrations. This reduces exposure and simplifies access management.

For teams running multiple integrations, OAuth 2.0 is the practical choice for secure, maintainable access to Matomo data. Every connection is authorised, bounded, and straightforward to control.

---

# Features

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
- Client management UI with secret rotation

---

# OAuth Endpoints

| Endpoint | Description |
|--------|-------------|
| `/index.php?module=OAuth2&action=authorize` | Authorization endpoint |
| `/index.php?module=OAuth2&action=token` | Token endpoint |

Optional cleaner routes can be added:

```
/oauth2/authorize
/oauth2/token
```

---

# Setup

## 1. Create an OAuth Client

Navigate to:

```
Administration → Platform → OAuth 2.0 (On-premise)
dministration → Export → OAuth 2.0 (Matomo Cloud)
```

Create a client and configure:

- Client type
  - **Confidential** (requires client secret)
  - **Public** (no secret)
- Allowed grant types
- Allowed scopes
- Redirect URI (required for Authorization Code flow)

Example client:

```
Client ID: analytics_app
Client Secret: 7fa9c0f81b8b4a12
Redirect URI: https://example-app.com/oauth/callback
```

---

# OAuth Flow Overview

1. Client redirects the user to `/authorize`
2. User logs into Matomo and approves access
3. Matomo redirects back with an authorization `code`
4. Client exchanges the `code` for an access token at `/token`
5. Client calls Matomo APIs using

```
Authorization: Bearer ACCESS_TOKEN
```

---

# Authorization Code Flow

## Step 1 — Generate PKCE parameters (Public Clients)

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

---

## Step 2 — Redirect user to Authorization Endpoint

### PKCE Example (Public Client)

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

### Confidential Client Example

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
2. Review requested permissions
3. Click **Allow**

Matomo will redirect back:

```
https://example-app.com/oauth/callback?code=AUTHORIZATION_CODE&state=abc123
```

---

# Exchange Authorization Code for Tokens

**Note:** The scope should be same as client scope and only one scope is allowed at the moment.

## PKCE Token Request

```
curl -X POST 'https://matomo.example.com/index.php?module=OAuth2&action=token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=authorization_code' \
  -d 'client_id=analytics_app' \
  -d 'redirect_uri=https://example-app.com/oauth/callback' \
  -d 'code=AUTHORIZATION_CODE' \
  -d 'code_verifier=dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'
```

## Confidential Client Token Request

```
curl -X POST 'https://matomo.example.com/index.php?module=OAuth2&action=token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=authorization_code' \
  -d 'client_id=analytics_app' \
  -d 'client_secret=7fa9c0f81b8b4a12' \
  -d 'redirect_uri=https://example-app.com/oauth/callback' \
  -d 'code=AUTHORIZATION_CODE'
```

---

# Client Credentials Flow (Server-to-Server)

The **Client Credentials grant** is used when a backend service needs to access Matomo APIs **without user interaction**.

Typical use cases:

- Internal analytics dashboards
- Scheduled data exports
- Backend integrations

## Token Request

```
curl -X POST 'https://matomo.example.com/index.php?module=OAuth2&action=token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d 'grant_type=client_credentials' \
  -d 'client_id=analytics_app' \
  -d 'client_secret=7fa9c0f81b8b4a12' \
  -d 'scope=matomo:read'
```

---

# Example Token Response

```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "def50200a1b9"
}
```

---

# Refresh Token

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

---

# Calling Matomo APIs with OAuth 2.0

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

---

# Security Notes

- Never expose **client secrets** in frontend applications.
- Public clients **must use PKCE**.
- Always use **HTTPS**.
- Store access tokens securely.
- Rotate client secrets periodically.
