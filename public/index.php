<?php

declare(strict_types=1);

use App\Foundation\Application;
use Witals\Framework\Server\ServerFactory;
use Witals\Framework\Contracts\RuntimeType;

require_once __DIR__ . '/../vendor/autoload.php';

$runtime = RuntimeType::detect();

$app = new Application(dirname(__DIR__), $runtime);

$server = ServerFactory::create($runtime, $app);

$server->start();
