<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Activity;

use Piwik\Piwik;

class PauseClientOfDeletedOwner extends BaseActivity
{
    protected $eventName = 'OAuth2.pauseClientOfDeletedOwner.end';

    public function extractParams($eventData)
    {
        if (empty($eventData[0]) || !is_array($eventData[0])) {
            return false;
        }

        $activityData = $eventData[0];
        $client = $activityData['client'] ?? [];
        $ownerLogin = (string) ($activityData['ownerLogin'] ?? '');

        if (empty($client['client_id']) || $ownerLogin === '') {
            return false;
        }

        return [
            'version' => 'v1',
            'client' => $this->formatClientData($client),
            'ownerLogin' => $ownerLogin,
        ];
    }

    public function getTranslatedDescription($activityData, $performingUser)
    {
        $clientLabel = $this->getClientLabel($activityData['client'] ?? []);
        $ownerLogin = (string) ($activityData['ownerLogin'] ?? '');

        return Piwik::translate('OAuth2_PauseClientOfDeletedOwnerActivity', [$clientLabel, $ownerLogin]);
    }
}
