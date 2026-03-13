<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Piwik\Access;
use Piwik\Container\StaticContainer;
use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Option;
use Piwik\Plugin;
use Piwik\Plugins\OAuth2\Access\OAuth2Access;
use Piwik\Plugins\OAuth2\Auth\Oauth2Auth;
use Piwik\Plugins\OAuth2\Auth\ResourceServerAuthenticator;

class OAuth2 extends Plugin
{

    public const PRIVATE_KEY_FILE_NAME = 'oauth-private.key';
    public const PUBLIC_KEY_FILE_NAME = 'oauth-public.key';
    public const OAUTH2_PRIVATE_OPTION_KEY = 'oauth2_private';
    public const OAUTH2_PUBLIC_OPTION_KEY = 'oauth2_public';
    public const OAUTH2_ENCRYPTION_OPTION_KEY = 'oauth2_encryption';
    public function registerEvents()
    {
        return [
            'API.Request.authenticate' => 'onApiAuthenticate',
            'API.Request.dispatch' => 'onApiRequestDispatch',
            'Db.getTablesInstalled' => 'getTablesInstalled',
            'Vue.getComponents' => 'registerVueComponents',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
        ];
    }

    public function onApiAuthenticate($tokenAuth)
    {
        // handled in Authentication listener class to keep plugin wiring contained
        $authorizationHeader = self::getAuthorizationHeader();
        $hasBearer = !empty($authorizationHeader) && strpos($authorizationHeader, 'Bearer ') === 0;
        if ($hasBearer) {
            StaticContainer::get(ResourceServerAuthenticator::class)->prepareAuthenticationFromToken($tokenAuth);
        }
    }

    public function onApiRequestDispatch(&$finalParameters, $pluginName, $methodName)
    {
        $tokenAuth = Access::getInstance()->getTokenAuth();
        if (empty($tokenAuth) || strncmp($tokenAuth, 'oauth2:', 7) !== 0) {
            return;
        }

        $auth = StaticContainer::get('Piwik\Auth');
        if (!$auth instanceof Oauth2Auth) {
            return;
        }

        $scopes = (array) ($auth->scopes ?? []);
        $access = Access::getInstance();
        OAuth2Access::loadSitesIfNeededFor($access);
        $siteAccess = OAuth2Access::getSiteAccessFor($access);
        $siteAccess = $this->modifyAccessBasedOnScope($siteAccess, $scopes[0] ?? null);
        if ($access->hasSuperUserAccess() && empty($siteAccess['superuser'])) {
            $access->setSuperUserAccess(false);
        }
        OAuth2Access::setSiteAccessFor($access, $siteAccess);
    }

    public function registerVueComponents(&$components)
    {
        $components[] = 'plugins/Oauth2/vue/dist/Oauth2AdminApp';
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'OAuth2_PlatformMenu';
        $translationKeys[] = 'OAuth2_AuthorizeTitle';
        $translationKeys[] = 'OAuth2_AuthorizeIntro';
        $translationKeys[] = 'OAuth2_RequestedScopes';
        $translationKeys[] = 'OAuth2_Allow';
        $translationKeys[] = 'OAuth2_Deny';
        $translationKeys[] = 'OAuth2_AdminHeading';
        $translationKeys[] = 'OAuth2_AdminClients';
        $translationKeys[] = 'OAuth2_AdminNoClients';
        $translationKeys[] = 'OAuth2_AdminClientId';
        $translationKeys[] = 'OAuth2_AdminClientCreatedAt';
        $translationKeys[] = 'OAuth2_AdminClientType';
        $translationKeys[] = 'OAuth2_AdminClientGrants';
        $translationKeys[] = 'OAuth2_AdminClientRedirects';
        $translationKeys[] = 'OAuth2_AdminClientStatus';
        $translationKeys[] = 'OAuth2_AdminClientActions';
        $translationKeys[] = 'OAuth2_AdminActive';
        $translationKeys[] = 'OAuth2_AdminDisabled';
        $translationKeys[] = 'OAuth2_AdminRotateSecret';
        $translationKeys[] = 'OAuth2_AdminDelete';
        $translationKeys[] = 'OAuth2_AdminCreateTitle';
        $translationKeys[] = 'OAuth2_AdminName';
        $translationKeys[] = 'OAuth2_AdminDescription';
        $translationKeys[] = 'OAuth2_AdminType';
        $translationKeys[] = 'OAuth2_AdminConfidential';
        $translationKeys[] = 'OAuth2_AdminPublic';
        $translationKeys[] = 'OAuth2_AdminGrantAuthorizationCode';
        $translationKeys[] = 'OAuth2_AdminGrantClientCredentials';
        $translationKeys[] = 'OAuth2_AdminGrantRefreshToken';
        $translationKeys[] = 'OAuth2_AdminScope';
        $translationKeys[] = 'OAuth2_AdminScopes';
        $translationKeys[] = 'OAuth2_AdminRedirectUris';
        $translationKeys[] = 'OAuth2_AdminActiveLabel';
        $translationKeys[] = 'OAuth2_AdminSave';
        $translationKeys[] = 'OAuth2_AdminSecretMessage';
        $translationKeys[] = 'OAuth2_AdminSecretHelp';
        $translationKeys[] = 'OAuth2_AdminCreated';
        $translationKeys[] = 'OAuth2_AdminRotated';
        $translationKeys[] = 'OAuth2_AdminDeleted';
        $translationKeys[] = 'OAuth2_AdminLoading';
        $translationKeys[] = 'OAuth2_AdminDeleteConfirm';
        $translationKeys[] = 'OAuth2_AdminRotateConfirm';
        $translationKeys[] = 'OAuth2_AdminClientsDescriptions';
        $translationKeys[] = 'OAuth2_AdminNameHelp';
        $translationKeys[] = 'OAuth2_AdminDescriptionHelp';
        $translationKeys[] = 'OAuth2_AdminTypeHelp';
        $translationKeys[] = 'OAuth2_AdminGrantTypesHelp';
        $translationKeys[] = 'OAuth2_AdminScopeHelp';
        $translationKeys[] = 'OAuth2_AdminRedirectUrisHelp';
        $translationKeys[] = 'OAuth2_AdminActiveHelp';
        $translationKeys[] = 'OAuth2_ErrorXNotProvided';
    }

    public function getTablesInstalled(&$allTablesInstalled)
    {
        $allTablesInstalled[] = Common::prefixTable('oauth2_client');
        $allTablesInstalled[] = Common::prefixTable('oauth2_access_token');
        $allTablesInstalled[] = Common::prefixTable('oauth2_refresh_token');
        $allTablesInstalled[] = Common::prefixTable('oauth2_auth_code');
    }

    public function activate()
    {
        self::setupRSAKeys();
        self::setEncryptionKey();
    }

    public function install()
    {
        DbHelper::createTable(
            'oauth2_client',
            "client_id VARCHAR(80) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            secret_hash VARCHAR(255) NULL,
            redirect_uris LONGTEXT NULL,
            grant_types LONGTEXT NULL,
            scopes LONGTEXT NULL,
            type VARCHAR(20) NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            owner_login VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            last_used_at DATETIME NULL,
            PRIMARY KEY (client_id),
            INDEX idx_oauth2_client_owner (owner_login)"
        );

        DbHelper::createTable(
            'oauth2_access_token',
            "token_id VARCHAR(128) NOT NULL,
            user_login VARCHAR(100) NULL,
            client_id VARCHAR(80) NOT NULL,
            scopes LONGTEXT NULL,
            revoked TINYINT(1) NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (token_id),
            INDEX idx_oauth2_access_client (client_id),
            INDEX idx_oauth2_access_expires (expires_at)"
        );

        DbHelper::createTable(
            'oauth2_refresh_token',
            "token_id VARCHAR(128) NOT NULL,
            access_token_id VARCHAR(128) NOT NULL,
            revoked TINYINT(1) NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (token_id),
            INDEX idx_oauth2_refresh_access (access_token_id)"
        );

        DbHelper::createTable(
            'oauth2_auth_code',
            "code_id VARCHAR(128) NOT NULL,
            user_login VARCHAR(100) NOT NULL,
            client_id VARCHAR(80) NOT NULL,
            scopes LONGTEXT NULL,
            redirect_uri LONGTEXT NULL,
            code_challenge VARCHAR(255) NULL,
            code_challenge_method VARCHAR(10) NULL,
            revoked TINYINT(1) NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (code_id),
            INDEX idx_oauth2_authcode_client (client_id),
            INDEX idx_oauth2_authcode_expires (expires_at)"
        );
    }

    public function uninstall()
    {
        foreach (['oauth2_auth_code', 'oauth2_refresh_token', 'oauth2_access_token', 'oauth2_client'] as $table) {
            Db::query('DROP TABLE IF EXISTS ' . Common::prefixTable($table));
        }
    }

    /**
     * @param $type
     * @return string
     */
    public static function getRSAKey($type = 'private'): string
    {
        $optionKey = $type === 'private' ? self::OAUTH2_PRIVATE_OPTION_KEY : self::OAUTH2_PUBLIC_OPTION_KEY;

        $optionValue = Option::get($optionKey);

        return $optionValue ?? '';
    }

    /**
     * @return string
     */
    public static function getEncryptionKey(): string
    {
        $value = Option::get(self::OAUTH2_ENCRYPTION_OPTION_KEY);

        return $value ?? '';
    }

    /**
     * @param $isForce
     * @return bool
     */
    public static function setupRSAKeys($isForce = false): bool
    {
        $privateKeyValue = Option::get(self::OAUTH2_PRIVATE_OPTION_KEY);
        $publicKeyValue = Option::get(self::OAUTH2_PUBLIC_OPTION_KEY);
        if (
            (!empty($publicKeyValue) && !empty($privateKeyValue) && !$isForce)
            || !defined('OPENSSL_KEYTYPE_RSA')
            || !function_exists('openssl_pkey_new')
            || !function_exists('openssl_pkey_export')
            || !function_exists('openssl_pkey_get_details')
        ) {
            return false;
        }
        $config = [
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        if ($res === false) {
            return false;
        }
        openssl_pkey_export($res, $privateKey);
        $publicKeyDetails = openssl_pkey_get_details($res);
        $publicKey = $publicKeyDetails['key'];
        Option::set(self::OAUTH2_PRIVATE_OPTION_KEY, $privateKey);
        Option::set(self::OAUTH2_PUBLIC_OPTION_KEY, $publicKey);

        return true;
    }

    /**
     * @param $isForce
     * @return void
     * @throws \Random\RandomException
     */
    public static function setEncryptionKey($isForce = false): void
    {
        $value = Option::get(self::OAUTH2_ENCRYPTION_OPTION_KEY);
        if (!$value || $isForce) {
            Option::set(self::OAUTH2_ENCRYPTION_OPTION_KEY, base64_encode(random_bytes(32)));
        }
    }



    public static function getAuthorizationHeader(): ?string
    {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (!empty($headers['Authorization'])) {
                return $headers['Authorization'];
            }
        }

        return null;
    }

    private function modifyAccessBasedOnScope(?array $idSitesAccess, ?string $scope): array
    {
        $levels = ['view' => 1, 'write' => 2, 'admin' => 3, 'superuser' => 4];
        $scopeToLevelMapping = ['matomo:read' => 'view', 'matomo:write' => 'write', 'matomo:admin' => 'admin', 'matomo:superuser' => 'superuser'];
        if (empty($scopeToLevelMapping[$scope])) {
            return [];
        }

        $target = $scopeToLevelMapping[$scope];
        $targetLevel = $levels[$target];

        foreach ($levels as $access => $level) {
            if ($level <= $targetLevel) {
                continue;
            }
            // Assign all the capabilities of a superuser too
            if ($access === 'superuser' && !empty($idSitesAccess['superuser'])) {
                $capabilityProvider = StaticContainer::get('Piwik\Access\CapabilitiesProvider');
                foreach ($capabilityProvider->getAllCapabilities() as $capability) {
                    if ($capability->hasRoleCapability($target)) {
                        $idSitesAccess[$capability->getId()] = $idSitesAccess['superuser'];
                    }
                }
            }
            $idSitesAccess[$target] = array_values(array_unique(array_merge(
                $idSitesAccess[$target] ?? [],
                $idSitesAccess[$access] ?? []
            )));
            $idSitesAccess[$access] = [];
        }

        return $idSitesAccess;
    }
}
