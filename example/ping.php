<?php

require_once('common.php');

$res = $client->ping();
print_r($res);

$client->disconnect();
