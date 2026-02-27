<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

use Piwik\Plugins\OAuth2\Auth\ResourceServerAuthenticator;
use Piwik\Plugins\OAuth2\Model\AccessTokenModel;
use Piwik\Plugins\OAuth2\Model\AuthCodeModel;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Model\RefreshTokenModel;
use Piwik\Plugins\OAuth2\Repositories\AccessTokenRepository;
use Piwik\Plugins\OAuth2\Repositories\AuthCodeRepository;
use Piwik\Plugins\OAuth2\Repositories\ClientRepository;
use Piwik\Plugins\OAuth2\Repositories\RefreshTokenRepository;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Plugins\OAuth2\Service\ServerFactory;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

return [
    ResourceServerAuthenticator::class => Piwik\DI::autowire(),
    ServerFactory::class => Piwik\DI::autowire(),
    ClientModel::class => Piwik\DI::autowire(),
    AccessTokenModel::class => Piwik\DI::autowire(),
    RefreshTokenModel::class => Piwik\DI::autowire(),
    AuthCodeModel::class => Piwik\DI::autowire(),
    ClientRepository::class => Piwik\DI::autowire(),
    AccessTokenRepository::class => Piwik\DI::autowire(),
    RefreshTokenRepository::class => Piwik\DI::autowire(),
    AuthCodeRepository::class => Piwik\DI::autowire(),
    ScopeRepository::class => Piwik\DI::autowire(),
    ClientManager::class => Piwik\DI::autowire(),
    'diagnostics.required' => Piwik\DI::add([
        Piwik\DI::get(\Piwik\Plugins\OAuth2\Diagnostic\OpenSslRsaCheck::class),
    ]),
];
