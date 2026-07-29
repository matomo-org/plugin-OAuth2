<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Activity;

class AuthorizeClient extends BaseActivity
{
    protected $eventName = 'OAuth2.authorize.decision.end';

    public function extractParams($eventData)
    {
        if (empty($eventData[0]) || !is_array($eventData[0])) {
            return false;
        }

        $activityData = $eventData[0];
        $client = $activityData['client'] ?? [];
        $decision = $activityData['decision'] ?? '';

        if (empty($client['id']) || !in_array($decision, ['allowed', 'denied'], true)) {
            return false;
        }

        $grantedScope = $activityData['grantedScope'] ?? null;

        return [
            'version' => 'v1',
            'client' => $client,
            'userLogin' => $activityData['userLogin'] ?? null,
            // the scopes the client requested, of which at most one can be granted
            'scopes' => array_values((array) ($activityData['scopes'] ?? [])),
            'grantedScope' => $decision === 'allowed' && is_string($grantedScope) ? $grantedScope : null,
            'decision' => $decision,
        ];
    }

    public function getTranslatedDescription($activityData, $performingUser)
    {
        $client = $activityData['client'] ?? [];
        $decision = $activityData['decision'] ?? '';

        if ($decision === 'allowed') {
            return sprintf('allowed OAuth 2.0 authorization request for client "%s"', $this->getClientLabel($client));
        }

        return sprintf('denied OAuth 2.0 authorization request for client "%s"', $this->getClientLabel($client));
    }
}
