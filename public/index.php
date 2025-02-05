<?php

use App\Kernel;

$projectDir = dirname(__DIR__);
$vendorDir = $projectDir . '/vendor';
$autoloadPath = $vendorDir . '/autoload_runtime.php';

if (!file_exists($autoloadPath)) {
    die('Could not find autoload_runtime.php. Did you run composer install?');
}

require_once $autoloadPath;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
