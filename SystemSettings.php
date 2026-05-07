<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Piwik\Piwik;
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
        $this->title = Piwik::translate('OAuth2_SystemSettingOauthTitle');
        $this->accessTokenTtl = $this->makeSetting('accessTokenTtl', 3600, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = Piwik::translate('OAuth2_SystemSettingOAuthAccessTokenLifetimeTitle');
            $field->description = Piwik::translate('OAuth2_SystemSettingOAuthAccessTokenLifetimeDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validate = function ($value) {
                if ($value <= 0 || !is_numeric($value)) {
                    throw new \Exception(Piwik::translate('OAuth2_InvalidNumericValueException'));
                }
            };
        });

        $this->refreshTokenTtl = $this->makeSetting('refreshTokenTtl', 2592000, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = Piwik::translate('OAuth2_SystemSettingOAuthRefreshTokenLifetimeTitle');
            $field->description = Piwik::translate('OAuth2_SystemSettingOAuthRefreshTokenLifetimeDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validate = function ($value) {
                if ($value <= 0 || !is_numeric($value)) {
                    throw new \Exception(Piwik::translate('OAuth2_InvalidNumericValueException'));
                }
            };
        });

        $this->authCodeTtl = $this->makeSetting('authCodeTtl', 600, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = Piwik::translate('OAuth2_SystemSettingOAuthAuthorizationCodeLifetimeTitle');
            $field->description = Piwik::translate('OAuth2_SystemSettingOAuthAuthorizationCodeLifetimeDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validate = function ($value) {
                if ($value <= 0 || !is_numeric($value)) {
                    throw new \Exception(Piwik::translate('OAuth2_InvalidNumericValueException'));
                }
            };
        });

        $this->enableAuthorizationCode = $this->makeSetting('enableAuthorizationCode', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('OAuth2_SystemSettingOAuthEnableAuthorizationCodeTitle');
        });

        $this->enableClientCredentials = $this->makeSetting('enableClientCredentials', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('OAuth2_SystemSettingOAuthEnableAClientCredentialsTitle');
        });

        $this->enableRefreshTokens = $this->makeSetting('enableRefreshTokens', true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Piwik::translate('OAuth2_SystemSettingOAuthEnableRefreshTokenTitle');
        });

        $scopes = OAuth2::getScopeDescriptions();
        $defaultScopes = $scopes;
        unset($defaultScopes['matomo:superuser']);
        $this->defaultScopes = $this->makeSetting('defaultScopes', array_keys($defaultScopes), FieldConfig::TYPE_ARRAY, function (FieldConfig $field) use ($scopes) {
            $field->title = Piwik::translate('OAuth2_SystemSettingOAuthScopeTitle');
            $field->description = Piwik::translate('OAuth2_SystemSettingOAuthScopeDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->availableValues = $scopes;
        });
    }
}
