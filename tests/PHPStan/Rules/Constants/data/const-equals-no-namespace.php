<?php

const CONSOLE_PATH = __DIR__ . '/../../bin/console';
[
	'command'  => CONSOLE_PATH . ' cron:update-popular-today --no-debug',
	'schedule' => '0 */6 * * *',
	'output'   => 'logs/update-popular-today.log',
];

define('ANOTHER_PATH', 'test');

echo ANOTHER_PATH;

function () {
	echo CONSOLE_PATH;
	echo ANOTHER_PATH;
};
