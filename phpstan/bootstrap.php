<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap-phpstan.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
