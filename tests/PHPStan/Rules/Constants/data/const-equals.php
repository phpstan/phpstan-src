<?php

namespace ConstEquals;

const CONSOLE_PATH = __DIR__ . '/../../bin/console';
[
	'command'  => CONSOLE_PATH . ' cron:update-popular-today --no-debug',
	'schedule' => '0 */6 * * *',
	'output'   => 'logs/update-popular-today.log',
];

define('ConstEquals\\ANOTHER_PATH', 'test');

echo ANOTHER_PATH;

define('DiffNamespace\\ANOTHER_PATH', 'test');

echo \DiffNamespace\ANOTHER_PATH;

function () {
	echo CONSOLE_PATH;
	echo ANOTHER_PATH;
	echo \DiffNamespace\ANOTHER_PATH;
};
