<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Oauth2;

use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\SystemSettings as BaseSystemSettings;
use Piwik\Settings\Setting;
use Piwik\Validators\NotEmpty;

class SystemSettings extends BaseSystemSettings
{
    public Setting $privateKeyPath;
    public Setting $publicKeyPath;
    public Setting $encryptionKey;
    public Setting $accessTokenTtl;
    public Setting $refreshTokenTtl;
    public Setting $authCodeTtl;
    public Setting $enableAuthorizationCode;
    public Setting $enableClientCredentials;
    public Setting $enableRefreshTokens;
    public Setting $defaultScopes;

    protected function init()
    {
        $this->privateKeyPath = $this->makeSetting('privateKeyPath', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Private key path';
            $field->description = 'Filesystem path to the RSA private key used to sign access tokens.';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validators[] = new NotEmpty();
        });

        $this->publicKeyPath = $this->makeSetting('publicKeyPath', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Public key path';
            $field->description = 'Filesystem path to the RSA public key used to validate access tokens.';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validators[] = new NotEmpty();
        });

        $this->encryptionKey = $this->makeSetting('encryptionKey', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Token encryption key';
            $field->description = 'Random 32+ character string to encrypt auth codes and refresh tokens (example: base64-encoded random bytes). Required.';
            $field->uiControl = FieldConfig::UI_CONTROL_PASSWORD;
            $field->validators[] = new NotEmpty();
        });

        $this->accessTokenTtl = $this->makeSetting('accessTokenTtl', 3600, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = 'Access token lifetime (seconds)';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });

        $this->refreshTokenTtl = $this->makeSetting('refreshTokenTtl', 2592000, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = 'Refresh token lifetime (seconds)';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });

        $this->authCodeTtl = $this->makeSetting('authCodeTtl', 600, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = 'Authorization code lifetime (seconds)';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });

        $this->enableAuthorizationCode = $this->makeSetting('enableAuthorizationCode', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = 'Enable authorization code grant (PKCE supported)';
        });

        $this->enableClientCredentials = $this->makeSetting('enableClientCredentials', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = 'Enable client credentials grant';
        });

        $this->enableRefreshTokens = $this->makeSetting('enableRefreshTokens', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = 'Enable refresh tokens';
        });

        $this->defaultScopes = $this->makeSetting('defaultScopes', ['matomo:read', 'matomo:write', 'matomo:superuser', 'offline_access'], FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = 'Allowed scopes';
            $field->description = 'Scopes available to OAuth2 clients. Remove entries to disable them globally.';
            $field->uiControl = FieldConfig::UI_CONTROL_MULTI_SELECT;
            $field->availableValues = [
                'matomo:read' => 'Read analytics data you can access.',
                'matomo:write' => 'Create and modify analytics configuration.',
                'matomo:superuser' => 'Matomo superuser-level operations.',
                'offline_access' => 'Access Matomo when you’re not actively using it.',
            ];
        });
    }
}
