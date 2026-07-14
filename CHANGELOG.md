## Changelog

5.2.2 - 2026-07-20
- Added code to harden check and disallow app specific token action

5.2.1 - 2026-07-13
- Added code to revoke tokens on client downgrade to public

5.2.0 - 2026-07-06
- Added support for the `/.well-known/oauth-authorization-server` discovery endpoint (RFC 8414), serving the authorization server metadata as JSON.
- Added the `OAuth2.authorizationServerMetadata` event so other plugins can extend the discovery document.
- Added code to improve grant policy check

5.1.1 - 2026-06-17
- Fixes scope-limited OAuth tokens owned by a super user failing all API requests with a "requires superuser access" error.
- Added code to reconcile capability permission based on scope

5.1.0 - 2026-06-08
- Added password confirmation for add, edit, delete and rotate token action. 

5.0.4 - 2026-05-25
- Added code to log activity on auth approve and deny
- Added code to log warning on auth failure
- Improved isRevoked checked to check for client status also

5.0.3 - 2026-05-11
- Added code to show token and authorize URL at the top of list screen
- Added better validation check for redirect URL, setting values and show client secret along with success message

5.0.2 - 2026-04-27
- Updated API documentation
- Added code to show scope in the list view
- Updated and scoped dependencies to support lower and higher PHP (>=8.1) versions

5.0.1 - 2026-04-17
- Fixes README.md

5.0.0 - 2026-04-13
- Initial release to create OAuth 2.0 clients for token generation and API access
