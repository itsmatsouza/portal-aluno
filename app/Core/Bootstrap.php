<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Leilabrito\PortalAluno\Core\SessionManager;

date_default_timezone_set('America/Sao_Paulo');

$rootPath = dirname(__DIR__, 2);

$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->load();

SessionManager::start();