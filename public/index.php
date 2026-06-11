<?php

declare(strict_types=1);

use App\Foundation\Application;
use Witals\Framework\Server\ServerFactory;
use Witals\Framework\Contracts\RuntimeType;

require_once __DIR__ . '/../vendor/autoload.php';

$runtime = RuntimeType::detect();

$app = new Application(dirname(__DIR__), $runtime);

// === Config path customization ===
//
// Default: config files in {basePath}/config/
//
// Option 1: Relative to basePath
// $app->setConfigPaths('app/config');
//
// Option 2: Absolute path
// $app->setConfigPaths('/etc/myapp/config');
//
// Option 3: Multiple paths (first match wins)
// $app->setConfigPaths(['config', 'project/config']);
//
// Option 4: Add extra path without overriding defaults
// $app->addConfigPath('project/config');
//
// Supports both .php (returning array) and .json config files.

$server = ServerFactory::create($runtime, $app);

$server->start();
