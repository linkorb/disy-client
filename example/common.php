<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$username = getenv('DISY_USERNAME');
$password = getenv('DISY_PASSWORD');
$host = getenv('DISY_HOST');

if (!$username || !$password ||!$host) {
    throw new RuntimeException("Environment variables not configured correctly");
}

$connection = new AMQPStreamConnection($host, 5672, $username, $password);
$client = new \DiSy\Client\Client($connection);
