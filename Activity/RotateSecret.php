<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Activity;

class RotateSecret extends BaseActivity
{
    protected $eventName = 'API.OAuth2.rotateSecret.end';

    public function extractParams($eventData)
    {
        if (!is_array($eventData) || count($eventData) < 2) {
            return false;
        }

        list($result, $finalParameters) = $eventData;

        $clientId = $result['client_id'] ?? $finalParameters['parameters']['clientId'] ?? $finalParameters['parameters']['client_id'] ?? null;
        if (!$clientId) {
            return false;
        }

        return [
            'version' => 'v1',
            'client' => [
                'id' => $clientId,
            ],
            'action' => 'rotated_secret',
        ];
    }

    public function getTranslatedDescription($activityData, $performingUser)
    {
        $client = $activityData['client'] ?? [];

        return sprintf('rotated secret for OAuth 2.0 client "%s"', $this->getClientLabel($client));
    }
}
