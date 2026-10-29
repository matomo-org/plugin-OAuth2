<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Activity;

use Piwik\Piwik;
use Piwik\Plugins\ActivityLog\Activity\Activity;

abstract class BaseActivity extends Activity
{
    /**
     * ActivityLog indexes existing entries again when this is bumped, so bump it whenever getObject() changes
     */
    protected const OBJECT_INDEX_VERSION = 1;

    /**
     * Returns the client the activity acts on, for the activity log's "Filter by item" filter
     *
     * Only ActivityLog 6.1.0 and later calls this; on earlier versions it is an unused method.
     *
     * @param array<mixed> $parameters
     * @return array{type: string, id: string, name: ?string, context: ?string}|null
     */
    public function getObject(array $parameters): ?array
    {
        $id = self::toString($parameters['client']['id'] ?? null);

        if ($id === null || $id === '') {
            return null;
        }

        // deleting a client or rotating its secret logs only the id; null lets ActivityLog keep its last known name
        $name = self::toString($parameters['client']['name'] ?? null);

        return [
            'type'    => 'oauth_client',
            'id'      => $id,
            'name'    => $name === '' ? null : $name,
            'context' => '',
        ];
    }

    /**
     * Returns the name of the item type getObject() returns, as its heading in the activity log's "Filter by item" filter
     *
     * Only ActivityLog 6.1.0 and later calls this; on earlier versions it is an unused method.
     *
     * @return array<string, string>
     */
    public function getObjectTypeLabels(): array
    {
        return ['oauth_client' => Piwik::translate('OAuth2_AdminHeading')];
    }

    /**
     * @param mixed $value
     */
    private static function toString($value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    protected function formatClientData(array $client): array
    {
        return [
            'id' => $client['client_id'] ?? null,
            'name' => $client['name'] ?? null,
            'type' => $client['type'] ?? null,
            'active' => isset($client['active']) ? (bool) $client['active'] : null,
        ];
    }

    protected function getClientLabel(array $client): string
    {
        $name = $client['name'] ?? '';
        $id = $client['id'] ?? ($client['client_id'] ?? '');

        if ($name !== '') {
            return sprintf('%s (%s)', $name, $id);
        }

        return (string) $id;
    }
}
