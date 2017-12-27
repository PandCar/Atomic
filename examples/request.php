<?php

include '../src/Atomic.class.php';

$atom = new Atomic([
	'path_tmp' => __DIR__,
]);

$result = $atom->request('http://ip-api.com/json');

// or

$result = $atom->request([
	'url' => 'http://ip-api.com/json',
]);

var_dump($result);
