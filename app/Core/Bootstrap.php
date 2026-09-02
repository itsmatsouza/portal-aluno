<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$rootPath = dirname(__DIR__, 2);

$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->load();