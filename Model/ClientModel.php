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

class ClientModel
{
    private string $table;

    public function __construct()
    {
        $this->table = Common::prefixTable('oauth2_client');
    }

    public function all(): array
    {
        $rows = Db::fetchAll('SELECT * FROM ' . $this->table . ' ORDER BY updated_at DESC');
        return array_map([$this, 'hydrate'], $rows);
    }

    public function allByOwner(string $ownerLogin): array
    {
        $rows = Db::fetchAll('SELECT * FROM ' . $this->table . ' WHERE owner_login = ?', [$ownerLogin]);
        return array_map([$this, 'hydrate'], $rows);
    }

    public function find(string $clientId): ?array
    {
        $row = Db::fetchRow('SELECT * FROM ' . $this->table . ' WHERE client_id = ?', [$clientId]);
        if (empty($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function create(array $data): array
    {
        $now = Date::now()->getDatetime();
        $clientId = $data['client_id'] ?? $this->generateIdentifier();

        Db::query(
            'INSERT INTO ' . $this->table . ' (client_id, name, description, secret_hash, redirect_uris, grant_types, scopes, type, active, owner_login, created_at, updated_at, last_used_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)',
            [
                $clientId,
                $data['name'],
                $data['description'] ?? '',
                $data['secret_hash'] ?? null,
                $this->encodeList($data['redirect_uris'] ?? []),
                $this->encodeList($data['grant_types'] ?? []),
                $this->encodeList($data['scopes'] ?? []),
                $data['type'] ?? 'confidential',
                !empty($data['active']) ? 1 : 0,
                $data['owner_login'],
                $now,
                $now,
            ]
        );

        return $this->find($clientId);
    }

    public function update(string $clientId, array $data): void
    {
        $now = Date::now()->getDatetime();

        Db::query(
            'UPDATE ' . $this->table . ' SET name = ?, description = ?, secret_hash = ?, redirect_uris = ?, grant_types = ?, scopes = ?, type = ?, active = ?, owner_login = ?, updated_at = ? WHERE client_id = ?',
            [
                $data['name'],
                $data['description'] ?? '',
                $data['secret_hash'] ?? null,
                $this->encodeList($data['redirect_uris'] ?? []),
                $this->encodeList($data['grant_types'] ?? []),
                $this->encodeList($data['scopes'] ?? []),
                $data['type'] ?? 'confidential',
                !empty($data['active']) ? 1 : 0,
                $data['owner_login'],
                $now,
                $clientId,
            ]
        );
    }

    public function rotateSecret(string $clientId, ?string $secretHash): void
    {
        $now = Date::now()->getDatetime();
        Db::query(
            'UPDATE ' . $this->table . ' SET secret_hash = ?, updated_at = ? WHERE client_id = ?',
            [$secretHash, $now, $clientId]
        );
    }

    public function setActive(string $clientId, bool $active): void
    {
        $now = Date::now()->getDatetime();
        Db::query(
            'UPDATE ' . $this->table . ' SET active = ?, updated_at = ? WHERE client_id = ?',
            [$active ? 1 : 0, $now, $clientId]
        );
    }

    public function delete(string $clientId): void
    {
        Db::query('DELETE FROM ' . $this->table . ' WHERE client_id = ?', [$clientId]);
    }

    public function touchLastUsed(string $clientId): void
    {
        Db::query(
            'UPDATE ' . $this->table . ' SET last_used_at = ?, updated_at = ? WHERE client_id = ?',
            [Date::now()->getDatetime(), Date::now()->getDatetime(), $clientId]
        );
    }

    private function hydrate(array $row): array
    {
        $row['grant_types'] = $this->decodeList($row['grant_types'] ?? '');
        $row['scopes'] = $this->decodeList($row['scopes'] ?? '');
        $row['redirect_uris'] = $this->decodeList($row['redirect_uris'] ?? '');
        $row['active'] = (bool) $row['active'];

        return $row;
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

    private function generateIdentifier(): string
    {
        return bin2hex(random_bytes(16));
    }
}
