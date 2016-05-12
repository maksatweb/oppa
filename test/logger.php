<?php
include('inc.php');

$autoload = require(__dir__.'/../src/Autoload.php');
$autoload->register();

use Oppa\Logger;

$logger = new Logger();
$logger->setLevel(Logger::ALL);
$logger->setDirectory(__dir__.'/../.logs');

$result = $logger->log(Logger::INFO, 'log...');
prd($result);
