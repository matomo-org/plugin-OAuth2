<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Activity;

class DeleteClient extends BaseActivity
{
    protected $eventName = 'API.OAuth2.deleteClient.end';

    public function extractParams($eventData)
    {
        if (!is_array($eventData) || count($eventData) < 2) {
            return [];
        }

        list($result, $finalParameters) = $eventData;

        $clientId = $finalParameters['parameters']['clientId'] ?? $finalParameters['parameters']['client_id'] ?? null;
        if (!$clientId) {
            return [];
        }

        return [
            'version' => 'v1',
            'client' => [
                'id' => $clientId,
            ],
            'action' => 'deleted',
            'deleted' => (bool) ($result['deleted'] ?? false),
        ];
    }

    public function getTranslatedDescription($activityData, $performingUser)
    {
        $client = $activityData['client'] ?? [];

        return sprintf('deleted OAuth2 client "%s"', $this->getClientLabel($client));
    }
}
