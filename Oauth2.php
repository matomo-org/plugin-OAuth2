<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Oauth2;

use Piwik\Access;
use Piwik\Container\StaticContainer;
use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Exception\NoPrivilegesException;
use Piwik\Plugin;
use Piwik\Plugins\Oauth2\Auth\Oauth2Auth;
use Piwik\Plugins\Oauth2\Auth\ResourceServerAuthenticator;
use Piwik\Request\AuthenticationToken;

class Oauth2 extends Plugin
{
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
        $hasBearer = !empty($_SERVER['HTTP_AUTHORIZATION']) && strpos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === 0;
        $hasAccessToken = !empty($_POST['access_token']);
        if ($hasBearer || $hasAccessToken) {
            $incomingToken = $tokenAuth ?: ($_POST['access_token'] ?? null);
            StaticContainer::get(ResourceServerAuthenticator::class)->prepareAuthenticationFromToken($incomingToken);
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
        $nonReadScopes = array_filter($scopes, static function ($scope) {
            return $scope !== 'matomo:read' && $scope !== 'offline_access';
        });

        if (!empty($nonReadScopes)) {
            throw new NoPrivilegesException('Request not authorised, scope not allowed.');
        }

        if (!str_starts_with($methodName, 'get')) {
            throw new NoPrivilegesException('Request not authorised, scope not allowed.');
        }
    }

    public function registerVueComponents(&$components)
    {
        $components[] = 'plugins/Oauth2/vue/dist/Oauth2AdminApp';
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'Oauth2_PlatformMenu';
        $translationKeys[] = 'Oauth2_AuthorizeTitle';
        $translationKeys[] = 'Oauth2_AuthorizeIntro';
        $translationKeys[] = 'Oauth2_RequestedScopes';
        $translationKeys[] = 'Oauth2_Allow';
        $translationKeys[] = 'Oauth2_Deny';
        $translationKeys[] = 'Oauth2_AdminHeading';
        $translationKeys[] = 'Oauth2_AdminClients';
        $translationKeys[] = 'Oauth2_AdminNoClients';
        $translationKeys[] = 'Oauth2_AdminClientId';
        $translationKeys[] = 'Oauth2_AdminClientCreatedAt';
        $translationKeys[] = 'Oauth2_AdminClientType';
        $translationKeys[] = 'Oauth2_AdminClientGrants';
        $translationKeys[] = 'Oauth2_AdminClientRedirects';
        $translationKeys[] = 'Oauth2_AdminClientStatus';
        $translationKeys[] = 'Oauth2_AdminClientActions';
        $translationKeys[] = 'Oauth2_AdminActive';
        $translationKeys[] = 'Oauth2_AdminDisabled';
        $translationKeys[] = 'Oauth2_AdminRotateSecret';
        $translationKeys[] = 'Oauth2_AdminDelete';
        $translationKeys[] = 'Oauth2_AdminCreateTitle';
        $translationKeys[] = 'Oauth2_AdminName';
        $translationKeys[] = 'Oauth2_AdminDescription';
        $translationKeys[] = 'Oauth2_AdminType';
        $translationKeys[] = 'Oauth2_AdminConfidential';
        $translationKeys[] = 'Oauth2_AdminPublic';
        $translationKeys[] = 'Oauth2_AdminGrantAuthorizationCode';
        $translationKeys[] = 'Oauth2_AdminGrantClientCredentials';
        $translationKeys[] = 'Oauth2_AdminGrantRefreshToken';
        $translationKeys[] = 'Oauth2_AdminScopes';
        $translationKeys[] = 'Oauth2_AdminRedirectUris';
        $translationKeys[] = 'Oauth2_AdminActiveLabel';
        $translationKeys[] = 'Oauth2_AdminSave';
        $translationKeys[] = 'Oauth2_AdminSecretMessage';
        $translationKeys[] = 'Oauth2_AdminSecretHelp';
        $translationKeys[] = 'Oauth2_AdminCreated';
        $translationKeys[] = 'Oauth2_AdminRotated';
        $translationKeys[] = 'Oauth2_AdminDeleted';
        $translationKeys[] = 'Oauth2_AdminLoading';
        $translationKeys[] = 'Oauth2_AdminDeleteConfirm';
        $translationKeys[] = 'Oauth2_AdminRotateConfirm';
        $translationKeys[] = 'Oauth2_AdminClientsDescriptions';
        $translationKeys[] = 'Oauth2_AdminNameHelp';
        $translationKeys[] = 'Oauth2_AdminDescriptionHelp';
        $translationKeys[] = 'Oauth2_AdminTypeHelp';
        $translationKeys[] = 'Oauth2_AdminGrantTypesHelp';
        $translationKeys[] = 'Oauth2_AdminScopesHelp';
        $translationKeys[] = 'Oauth2_AdminRedirectUrisHelp';
        $translationKeys[] = 'Oauth2_AdminActiveHelp';
    }

    public function getTablesInstalled(&$allTablesInstalled)
    {
        $allTablesInstalled[] = Common::prefixTable('oauth2_client');
        $allTablesInstalled[] = Common::prefixTable('oauth2_access_token');
        $allTablesInstalled[] = Common::prefixTable('oauth2_refresh_token');
        $allTablesInstalled[] = Common::prefixTable('oauth2_auth_code');
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
}
