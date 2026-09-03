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
use Piwik\Log\LoggerInterface;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugins\OAuth2\Access\OAuth2Access;
use Piwik\Plugins\OAuth2\Auth\Oauth2Auth;
use Piwik\Plugins\OAuth2\Auth\ResourceServerAuthenticator;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Plugins\SitesManager\Model as SitesManagerModel;

class OAuth2 extends Plugin
{
    public const PRIVATE_KEY_FILE_NAME = 'oauth-private.key';
    public const PUBLIC_KEY_FILE_NAME = 'oauth-public.key';
    public const OAUTH2_PRIVATE_OPTION_KEY = 'oauth2_private';
    public const OAUTH2_PUBLIC_OPTION_KEY = 'oauth2_public';
    public const OAUTH2_ENCRYPTION_OPTION_KEY = 'oauth2_encryption';

    public static function getScopeDescriptions(): array
    {
        return [
            'matomo:read' => Piwik::translate('OAuth2_ScopeReadDescription'),
            'matomo:write' => Piwik::translate('OAuth2_ScopeWriteDescription'),
            'matomo:admin' => Piwik::translate('OAuth2_ScopeAdminDescription'),
            'matomo:superuser' => Piwik::translate('OAuth2_ScopeSuperUserDescription'),
        ];
    }

    /**
     * Maps each scope to the access role it confines a token to, and to its privilege level.
     * The level is what orders the scopes, a higher level includes every lower one.
     *
     * @return array<string, array{role: string, level: int}>
     */
    public static function getScopeAccessLevels(): array
    {
        return [
            'matomo:read' => ['role' => 'view', 'level' => 1],
            'matomo:write' => ['role' => 'write', 'level' => 2],
            'matomo:admin' => ['role' => 'admin', 'level' => 3],
            'matomo:superuser' => ['role' => 'superuser', 'level' => 4],
        ];
    }

    /**
     * Returns the given scope together with every less privileged scope, in ascending order.
     *
     * The scope configured for a client is the maximum access level its tokens may get, as
     * described by OAuth2_AdminScopeHelp, so a client allowed `matomo:admin` may also be
     * granted `matomo:write` or `matomo:read`. Unknown scopes expand to nothing.
     *
     * @return string[]
     */
    public static function expandScopeHierarchically(string $scope): array
    {
        $levels = self::getScopeAccessLevels();

        if (!isset($levels[$scope])) {
            return [];
        }

        $maximumLevel = $levels[$scope]['level'];
        $expanded = [];

        foreach ($levels as $identifier => $level) {
            if ($level['level'] <= $maximumLevel) {
                $expanded[] = $identifier;
            }
        }

        return $expanded;
    }

    public function registerEvents()
    {
        return [
            'API.Request.authenticate' => 'onApiAuthenticate',
            'API.Request.dispatch' => 'onApiRequestDispatch',
            'Access.modifyUserAccess' => 'onModifyUserAccess',
            'UsersManager.deleteUser' => ['function' => 'onUserDeleted', 'before' => true],
            'Db.getTablesInstalled' => 'getTablesInstalled',
            'Vue.getComponents' => 'registerVueComponents',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
        ];
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/OAuth2/stylesheets/oauth2.less";
    }

    /**
     * Removes the OAuth clients and credentials belonging to a deleted user.
     *
     * A login may be handed to a different person later on, and clients and tokens are associated
     * with it as plain text, so anything left behind here would authenticate as whoever holds the
     * login next. The listener is registered in the first callback group because
     * `EventDispatcher::postEvent()` does not isolate observers from each other, and a listener of
     * another plugin throwing before this one would otherwise skip the cleanup entirely.
     *
     * @param string $userLogin
     */
    public function onUserDeleted($userLogin)
    {
        $clientManager = StaticContainer::get(ClientManager::class);

        try {
            // The credentials go first, so that failing part way through leaves an unusable client
            // behind instead of a token that still authenticates.
            $clientManager->deleteCredentialsForUser($userLogin);

            foreach ($clientManager->deleteClientsForOwner($userLogin) as $client) {
                /**
                 * Triggered after an OAuth 2.0 client was removed because its owner was deleted.
                 *
                 * Clients removed this way never pass through the API, so `API.OAuth2.deleteClient.end`
                 * does not report them and this is the only event that does. The activity log relies
                 * on it to keep naming the clients a user deletion took with it.
                 *
                 * @param array $activityData Details of the removed client:
                 *
                 *                            - `version`: the payload version, currently `v1`.
                 *                            - `client`: the removed client, with `client_id`, `name`,
                 *                              `type` and `active`. The secret is never included.
                 *                            - `ownerLogin`: the login of the deleted owner.
                 */
                Piwik::postEvent('OAuth2.deleteClientWithOwner.end', [
                    [
                        'version' => 'v1',
                        // only the fields the activity log displays, never the secret hash
                        'client' => [
                            'client_id' => $client['client_id'] ?? null,
                            'name' => $client['name'] ?? null,
                            'type' => $client['type'] ?? null,
                            'active' => $client['active'] ?? null,
                        ],
                        'ownerLogin' => $userLogin,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            // UsersManager logs one generic message for the whole event, which is not enough to tell
            // that OAuth credentials outlived their owner, so name the failure here. It is not
            // rethrown, as this listener runs first and would otherwise skip the cleanup of every
            // other plugin. Whatever is left behind stays unusable, see ClientRepository.
            StaticContainer::get(LoggerInterface::class)->error(
                'Failed to remove the OAuth2 clients and credentials of the deleted user {login}',
                ['login' => $userLogin, 'exception' => $e]
            );
        }
    }

    public function onApiAuthenticate(
        #[\SensitiveParameter]
        $tokenAuth
    ) {
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

        // Not permitted for OAuth2-authenticated requests, regardless of scope.
        $blockedUsersManagerMethods = [
            'createAppSpecificTokenAuth' => 'OAuth2_CreateAppSpecificTokenAuthBlocked',
            'updateUser'                 => 'OAuth2_UpdateUserBlocked',
        ];
        if ($pluginName === 'UsersManager' && isset($blockedUsersManagerMethods[$methodName])) {
            throw new \Exception(Piwik::translate($blockedUsersManagerMethods[$methodName]));
        }

        // Not permitted for read-scope OAuth2 tokens; write and above may proceed.
        $readScopeBlockedMethods = ['setUserPreference', 'initUserPreferenceWithDefault'];
        if (
            $pluginName === 'UsersManager'
            && in_array($methodName, $readScopeBlockedMethods, true)
            && !$this->scopeGrantsAtLeastWrite($auth->getPrimaryScope())
        ) {
            throw new \Exception(Piwik::translate('OAuth2_SetUserPreferenceBlocked'));
        }

        $access = Access::getInstance();
        OAuth2Access::loadSitesIfNeededFor($access);
        $siteAccess = OAuth2Access::getSiteAccessFor($access);
        $siteAccess = $this->modifyAccessBasedOnScope($siteAccess, $auth->getPrimaryScope());
        if ($access->hasSuperUserAccess() && empty($siteAccess['superuser'])) {
            $access->setSuperUserAccess(false);
        }
        OAuth2Access::setSiteAccessFor($access, $siteAccess);
    }

    public function onModifyUserAccess(&$idsitesByAccess, $login): void
    {
        $auth = StaticContainer::get('Piwik\Auth');
        if (!$auth instanceof Oauth2Auth || $auth->getLogin() !== $login) {
            return;
        }

        if ($auth->isSubjectSuperUser() && !$auth->allowsSuperUserAccess()) {
            // Read site IDs from the model directly; SitesManager\API::getAllSitesId() requires
            // super user access, which the scope-limited token context doesn't have here.
            $idsitesByAccess['superuser'] = (new SitesManagerModel())->getSitesId();
        }

        $idsitesByAccess = $this->modifyAccessBasedOnScope($idsitesByAccess, $auth->getPrimaryScope());
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
        $translationKeys[] = 'OAuth2_AdminEdit';
        $translationKeys[] = 'OAuth2_AdminCreateTitle';
        $translationKeys[] = 'OAuth2_AdminEditTitle';
        $translationKeys[] = 'OAuth2_AdminBackToClients';
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
        $translationKeys[] = 'OAuth2_AdminUpdate';
        $translationKeys[] = 'OAuth2_AdminSecretMessage';
        $translationKeys[] = 'OAuth2_AdminSecretHelp';
        $translationKeys[] = 'OAuth2_AdminCreated';
        $translationKeys[] = 'OAuth2_AdminUpdated';
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
        $translationKeys[] = 'OAuth2_AdminScopeReducedWarning';
        $translationKeys[] = 'OAuth2_AdminRedirectUrisHelp';
        $translationKeys[] = 'OAuth2_AdminActiveHelp';
        $translationKeys[] = 'OAuth2_ErrorXNotProvided';
        $translationKeys[] = 'OAuth2_ClientSecret';
        $translationKeys[] = 'OAuth2_ClientSecretHelp';
        $translationKeys[] = 'OAuth2_ClientNotFound';
        $translationKeys[] = 'OAuth2_AdminPause';
        $translationKeys[] = 'OAuth2_AdminResume';
        $translationKeys[] = 'OAuth2_AdminResumed';
        $translationKeys[] = 'OAuth2_AdminPaused';
        $translationKeys[] = 'OAuth2_AdminPauseConfirm';
        $translationKeys[] = 'OAuth2_AdminResumeConfirm';
        $translationKeys[] = 'OAuth2_ClientSecretMaskedHelp';
        $translationKeys[] = 'OAuth2_ClientSecretVisibleHelp';
        $translationKeys[] = 'OAuth2_ClientSecretDisplayedNotification';
        $translationKeys[] = 'OAuth2_AdminDescriptionPlaceholder';
        $translationKeys[] = 'OAuth2_AdminRotatedNotification';
        $translationKeys[] = 'OAuth2_ScopeSuperUserShort';
        $translationKeys[] = 'UsersManager_PrivView';
        $translationKeys[] = 'UsersManager_PrivWrite';
        $translationKeys[] = 'UsersManager_PrivAdmin';
        $translationKeys[] = 'OAuth2_AdminClientDescriptionAdditionalHelpText';
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

        return $optionValue;
    }

    /**
     * @return string
     */
    public static function getEncryptionKey(): string
    {
        $value = Option::get(self::OAUTH2_ENCRYPTION_OPTION_KEY);

        return $value;
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

    private function scopeGrantsAtLeastWrite(?string $scope): bool
    {
        $scopeLevels = self::getScopeAccessLevels();

        // Unknown/empty scopes are treated as below write.
        $level = $scopeLevels[$scope]['level'] ?? 0;

        return $level >= $scopeLevels['matomo:write']['level'];
    }

    private function modifyAccessBasedOnScope(?array $idSitesAccess, ?string $scope): array
    {
        $scopeLevels = self::getScopeAccessLevels();

        if (!isset($scopeLevels[$scope])) {
            return [];
        }

        $levels = [];
        foreach ($scopeLevels as $scopeLevel) {
            $levels[$scopeLevel['role']] = $scopeLevel['level'];
        }

        $target = $scopeLevels[$scope]['role'];
        $targetLevel = $scopeLevels[$scope]['level'];

        // Capture the subject's super user sites before the demotion loop empties them,
        // so the capability reconciliation below can grant a super user every capability.
        $superUserSites = $idSitesAccess['superuser'] ?? [];

        foreach ($levels as $access => $level) {
            if ($level <= $targetLevel) {
                continue;
            }
            $idSitesAccess[$target] = array_values(array_unique(array_merge(
                $idSitesAccess[$target] ?? [],
                $idSitesAccess[$access] ?? []
            )));
            $idSitesAccess[$access] = [];
        }

        // Reconcile capabilities with the confined role. Core seeds capability keys
        // (e.g. tagmanager_publish_live_container) from the subject's real role, so a
        // lower-scoped token issued to a higher-privileged user would otherwise keep
        // capabilities the scope forbids. Strip every capability the target role does
        // not grant, for ALL subjects - not just super users. Skipped for the
        // superuser scope, which is unconfined.
        if ($targetLevel < $levels['superuser']) {
            $capabilityProvider = StaticContainer::get('Piwik\Access\CapabilitiesProvider');
            foreach ($capabilityProvider->getAllCapabilities() as $capability) {
                $capabilityId = $capability->getId();
                if (!$capability->hasRoleCapability($target)) {
                    // Capability sits above the confined scope - drop it entirely.
                    $idSitesAccess[$capabilityId] = [];
                    continue;
                }
                // A super user implicitly holds every retained capability on the sites
                // they administer, so seed those sites onto the capability.
                if (!empty($superUserSites)) {
                    $idSitesAccess[$capabilityId] = array_values(array_unique(array_merge(
                        $idSitesAccess[$capabilityId] ?? [],
                        $superUserSites
                    )));
                }
            }
        }

        return $idSitesAccess;
    }
}
