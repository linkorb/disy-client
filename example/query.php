<?php

require_once('common.php');

$res = $client->query('SELECT * FROM tblPatient WHERE patientkey=1');
print_r($res);


$res = $client->query('SELECT * FROM `tblPatient verslag` WHERE patientkey=1');
print_r($res);

$res = $client->query('SELECT `invoerdatum en tijd` FROM `tblBehandeling` WHERE patientkey=1');
print_r($res);


$client->disconnect();
