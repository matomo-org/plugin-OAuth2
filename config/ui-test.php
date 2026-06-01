<?php

use Piwik\Container\StaticContainer;

return [
    'observers.global' => Piwik\DI::add([
        ['Login.userRequiresPasswordConfirmation', Piwik\DI::value(function (&$requiresPasswordConfirmation) {
            if (!StaticContainer::get('test.vars.enablePasswordConfirmationForUITests')) {
                $requiresPasswordConfirmation = false;
            }
        })],
    ]),
];
