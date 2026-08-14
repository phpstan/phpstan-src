<?php

namespace Bug7155;

use function PHPStan\Testing\assertType;

/** @var \Closure|string $event */
$event = '';
$callback = null;

if ($event instanceof \Closure) {
	[$event, $callback] = ['event-name', $event];
}

assertType('string', $event);
assertType('Closure|null', $callback);
