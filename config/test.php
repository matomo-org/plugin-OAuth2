<?php

use Piwik\Plugins\OAuth2\Service\DiscoveryFile;

return [
    // Keep tests from writing the discovery file into the Matomo directory.
    DiscoveryFile::class => Piwik\DI::autowire()
        ->constructorParameter('documentRoot', Piwik\DI::string('{path.tmp}/oauth2-discovery')),
];
