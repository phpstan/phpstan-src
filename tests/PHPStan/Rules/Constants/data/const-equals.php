<?php

namespace ConstEquals;

const CONSOLE_PATH = __DIR__ . '/../../bin/console';
[
	'command'  => CONSOLE_PATH . ' cron:update-popular-today --no-debug',
	'schedule' => '0 */6 * * *',
	'output'   => 'logs/update-popular-today.log',
];
