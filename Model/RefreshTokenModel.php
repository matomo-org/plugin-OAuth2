<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Model;

use Piwik\Common;
use Piwik\Db;
use Piwik\Date;

class RefreshTokenModel
{
    private string $table;
    private string $accessTokenTable;
    private string $clientTable;

    public function __construct()
    {
        $this->table = Common::prefixTable('oauth2_refresh_token');
        $this->accessTokenTable = Common::prefixTable('oauth2_access_token');
        $this->clientTable = Common::prefixTable('oauth2_client');
    }

    public function persist(array $data): void
    {
        Db::query(
            'INSERT INTO ' . $this->table . ' (token_id, access_token_id, revoked, expires_at, created_at)
             VALUES (?, ?, 0, ?, ?)',
            [
                $data['token_id'],
                $data['access_token_id'],
                $data['expires_at'],
                Date::now()->getDatetime(),
            ]
        );
    }

    public function revoke(string $tokenId): void
    {
        Db::query('UPDATE ' . $this->table . ' SET revoked = 1 WHERE token_id = ?', [$tokenId]);
    }

    public function revokeByClient(string $clientId): void
    {
        Db::query(
            'UPDATE ' . $this->table . ' rt
             INNER JOIN ' . Common::prefixTable('oauth2_access_token') . ' at ON rt.access_token_id = at.token_id
             SET rt.revoked = 1
             WHERE at.client_id = ?',
            [$clientId]
        );
    }

    public function isRevoked(string $tokenId): bool
    {
        $row = Db::fetchRow(
            'SELECT token.revoked, client.active AS client_active
             FROM ' . $this->table . ' token
             LEFT JOIN ' . $this->accessTokenTable . ' access_token ON access_token.token_id = token.access_token_id
             LEFT JOIN ' . $this->clientTable . ' client ON client.client_id = access_token.client_id
             WHERE token.token_id = ?',
            [$tokenId]
        );

        return empty($row) || (bool) $row['revoked'] === true || empty($row['client_active']);
    }

    public function deleteByClient(string $clientId): void
    {
        Db::query(
            'DELETE rt FROM ' . $this->table . ' rt INNER JOIN ' . Common::prefixTable('oauth2_access_token') . ' at ON rt.access_token_id = at.token_id WHERE at.client_id = ?',
            [$clientId]
        );
    }

    public function deleteByUserLogin(string $userLogin): void
    {
        Db::query(
            'DELETE rt FROM ' . $this->table . ' rt INNER JOIN ' . Common::prefixTable('oauth2_access_token') . ' at ON rt.access_token_id = at.token_id WHERE at.user_login = ?',
            [$userLogin]
        );
    }
}
