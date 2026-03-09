<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\SystemSettings as BaseSystemSettings;
use Piwik\Settings\Setting;

class SystemSettings extends BaseSystemSettings
{
    public Setting $accessTokenTtl;
    public Setting $refreshTokenTtl;
    public Setting $authCodeTtl;
    public Setting $enableAuthorizationCode;
    public Setting $enableClientCredentials;
    public Setting $enableRefreshTokens;
    public Setting $defaultScopes;

    protected function init()
    {
        $this->title = 'OAuth 2.0';
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

        $scopes = ScopeRepository::DESCRIPTIONS;
        $defaultScopes = $scopes;
        if (isset($defaultScopes['matomo:superuser'])) {
            unset($defaultScopes['matomo:superuser']);
        }
        $this->defaultScopes = $this->makeSetting('defaultScopes', array_keys($defaultScopes), FieldConfig::TYPE_ARRAY, function (FieldConfig $field) use ($scopes) {
            $field->title = 'Allowed scopes';
            $field->description = 'Scopes available to OAuth2 clients. Remove entries to disable them globally.';
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->availableValues = $scopes;
        });
    }
}
