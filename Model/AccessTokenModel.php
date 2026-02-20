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

class AccessTokenModel
{
    private string $table;

    public function __construct()
    {
        $this->table = Common::prefixTable('oauth2_access_token');
    }

    public function persist(array $data): void
    {
        Db::query(
            'INSERT INTO ' . $this->table . ' (token_id, user_login, client_id, scopes, revoked, expires_at, created_at)
             VALUES (?, ?, ?, ?, 0, ?, ?)',
            [
                $data['token_id'],
                $data['user_login'],
                $data['client_id'],
                $this->encodeList($data['scopes'] ?? []),
                $data['expires_at'],
                Date::now()->getDatetime(),
            ]
        );
    }

    public function revoke(string $tokenId): void
    {
        Db::query('UPDATE ' . $this->table . ' SET revoked = 1 WHERE token_id = ?', [$tokenId]);
    }

    public function isRevoked(string $tokenId): bool
    {
        $row = Db::fetchRow('SELECT revoked FROM ' . $this->table . ' WHERE token_id = ?', [$tokenId]);
        return empty($row) || (bool) $row['revoked'] === true;
    }

    public function find(string $tokenId): ?array
    {
        $row = Db::fetchRow('SELECT * FROM ' . $this->table . ' WHERE token_id = ?', [$tokenId]);
        if (empty($row)) {
            return null;
        }

        $row['scopes'] = $this->decodeList($row['scopes'] ?? '');
        $row['revoked'] = (bool) $row['revoked'];

        return $row;
    }

    public function deleteByClient(string $clientId): void
    {
        Db::query('DELETE FROM ' . $this->table . ' WHERE client_id = ?', [$clientId]);
    }

    private function encodeList(array $values): string
    {
        $normalized = array_values(array_filter(array_map('trim', $values), static function ($value) {
            return $value !== '';
        }));

        return json_encode($normalized);
    }

    private function decodeList(string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('trim', $decoded), static function ($item) {
                return $item !== '';
            }));
        }

        $parts = preg_split('/[\r\n]+/', $value);
        if ($parts === false) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $parts), static function ($item) {
            return $item !== '';
        }));
    }
}
