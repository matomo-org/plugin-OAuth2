<?php

use Piwik\Container\StaticContainer;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;

return [
    // Keep tests from writing the discovery file into the Matomo directory.
    DiscoveryFile::class => Piwik\DI::autowire()
        ->constructorParameter('documentRoot', Piwik\DI::string('{path.tmp}/oauth2-discovery')),
    'observers.global' => Piwik\DI::add([
        ['Login.userRequiresPasswordConfirmation', Piwik\DI::value(function (&$requiresPasswordConfirmation) {
            if (!StaticContainer::get('test.vars.enablePasswordConfirmationForUITests')) {
                $requiresPasswordConfirmation = false;
            }
        })],
    ]),
];
