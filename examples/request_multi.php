<?php

include '../src/Atomic.class.php';

$atom = new Atomic([
	'path_tmp' => __DIR__,
]);

$result = $atom->request_multi([
	[
		'url' => 'http://ip-api.com/json',
		'form' => 'json',
	],
	[
		'url' => 'http://ip-api.com/json',
		'form' => 'json',
	]
]);

var_dump($result);
