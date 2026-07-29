<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Activity;

use Piwik\Piwik;

class UpdateClient extends BaseActivity
{
    protected $eventName = 'API.OAuth2.updateClient.end';

    public function extractParams($eventData)
    {
        if (!is_array($eventData) || count($eventData) < 2) {
            return false;
        }

        list($result) = $eventData;
        $client = $result['client'] ?? null;

        if (empty($client['client_id'])) {
            return false;
        }

        return [
            'version' => 'v1',
            'client' => $this->formatClientData($client),
            'action' => 'updated',
        ];
    }

    public function getTranslatedDescription($activityData, $performingUser)
    {
        $client = $activityData['client'] ?? [];

        return Piwik::translate('OAuth2_UpdateClientActivity', [$this->getClientLabel($client)]);
    }
}
