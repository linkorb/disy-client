<?php

require_once('common.php');

$res = $client->getFile('C:\\test.txt');
print_r($res);
$data = base64_decode($res['data']);
file_put_contents("/tmp/test", $data);

$client->disconnect();
