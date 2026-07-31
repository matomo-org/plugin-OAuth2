<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Activity;

use Piwik\Piwik;

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

        return [
            'version' => 'v1',
            'client' => $client,
            'userLogin' => $activityData['userLogin'] ?? null,
            // the scopes that were granted, empty when the request was denied
            'scopes' => $decision === 'allowed' ? array_values((array) ($activityData['scopes'] ?? [])) : [],
            'requestedScopes' => array_values((array) ($activityData['requestedScopes'] ?? [])),
            'decision' => $decision,
        ];
    }

    public function getTranslatedDescription($activityData, $performingUser)
    {
        $clientLabel = $this->getClientLabel($activityData['client'] ?? []);
        $decision = $activityData['decision'] ?? '';
        $granted = implode(', ', array_values((array) ($activityData['scopes'] ?? [])));
        $requested = implode(', ', array_values((array) ($activityData['requestedScopes'] ?? [])));

        // decisions recorded before these scopes were stored can only name the client
        if ($decision === 'allowed') {
            if ($granted === '') {
                return Piwik::translate('OAuth2_AuthorizeAllowedActivity', [$clientLabel]);
            }

            if ($requested === '') {
                return Piwik::translate('OAuth2_AuthorizeAllowedWithScopeActivity', [$clientLabel, $granted]);
            }

            return Piwik::translate(
                'OAuth2_AuthorizeAllowedWithScopeAndRequestActivity',
                [$clientLabel, $granted, $requested]
            );
        }

        if ($requested === '') {
            return Piwik::translate('OAuth2_AuthorizeDeniedActivity', [$clientLabel]);
        }

        return Piwik::translate('OAuth2_AuthorizeDeniedWithRequestActivity', [$clientLabel, $requested]);
    }
}
