# OAuth2 Plugin (Matomo first-party authorization server)

This plugin adds a first-party OAuth2 authorization server to Matomo. It lets you:

- Manage OAuth2 clients from the Matomo admin UI (Platform → OAuth2).
- Issue tokens via Authorization Code + PKCE, Client Credentials, and Refresh Token grants.
- Present an end-user consent screen for Authorization Code flows.
- Accept `Authorization: Bearer <access_token>` for Matomo API requests and map tokens to Matomo users.
- Rotate client secrets, disable/delete clients, and configure token lifetimes.

## Features

- **Grants:** Authorization Code (with PKCE), Client Credentials, Refresh Token.
- **Scopes:** `matomo:read`, `matomo:write`, `matomo:superuser`, `offline_access` (extendable).
- **Keys & crypto:** Uses RSA private/public key pair (Lcobucci JWT via league/oauth2-server).
- **UI:** Vue-powered admin screen for client CRUD + secret rotation.
- **API endpoint:** `/index.php?module=OAuth2&action=token` (alias `/oauth2/token` if you add routing) for JSON token responses.
- **Resource server:** Bearer tokens accepted on Matomo API calls; sets current user context based on the token subject.

## Setup

1) **Create clients (Admin → Platform → OAuth2)**
- Choose type: Confidential (requires secret) or Public (no secret).
- Set allowed grant types, scopes, and redirect URIs (required for Authorization Code).
- Save the client; copy the secret immediately (shown once) for confidential clients.

2) **Authorize & obtain tokens**
- **Authorization Code + PKCE:**
  - Authorization endpoint: `/index.php?module=OAuth2&action=authorize` (add an alias `/oauth2/authorize` if desired).
  - Include `response_type=code`, `client_id`, `redirect_uri`, `scope`, `state`, `code_challenge`, `code_challenge_method=S256`.
  - On approval, exchange `code` at `/index.php?module=OAuth2&action=token` with `grant_type=authorization_code`, `code_verifier`, `redirect_uri`, and client auth (secret for confidential clients).
- **Client Credentials:**
  - Token endpoint: `/index.php?module=Oauth2&action=token`
  - Body: `grant_type=client_credentials&scope=matomo:read` (or other allowed scopes).
  - Auth: HTTP Basic or `client_id`/`client_secret`.
- **Refresh Token:**
  - Body: `grant_type=refresh_token&refresh_token=<token>`.

3) **Call Matomo APIs with Bearer tokens**
- Add header: `Authorization: Bearer <access_token>`.
- The token subject sets the Matomo user context; permissions derive from scopes and the user’s Matomo rights.
- If both token_auth and Bearer are supplied, Bearer takes precedence (configurable in plugin code).

## Notes

- Keys/secrets must be kept secure and not world-readable.
- Public clients must use PKCE for Authorization Code.
- `offline_access` scope is required to receive refresh tokens.
- Consider adding route aliases (`/oauth2/authorize`, `/oauth2/token`) via Matomo routing if you want cleaner URLs.
