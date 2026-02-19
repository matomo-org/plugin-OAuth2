<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Activity;

use Piwik\Plugins\ActivityLog\Activity\Activity;

abstract class BaseActivity extends Activity
{
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
