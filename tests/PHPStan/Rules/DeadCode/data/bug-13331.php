<?php declare(strict_types = 1);

namespace Bug13331;

$signalHandler = function () {
	exit();
};

pcntl_async_signals(true);
pcntl_signal(SIGINT, $signalHandler);
pcntl_signal(SIGQUIT, $signalHandler);
pcntl_signal(SIGTERM, $signalHandler);
