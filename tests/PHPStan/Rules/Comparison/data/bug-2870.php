<?php declare(strict_types = 1);

$array = [];

for ($i = 0; $i < 100; $i++) {
	if (!in_array('test', $array, true) && (bool)rand()) {
		$array[] = 'test';
	}
}
