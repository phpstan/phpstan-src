<?php

namespace SessionHandlerRead;

use function PHPStan\Testing\assertType;

function (\SessionHandler $handler, \SessionHandlerInterface $interface): void {
	assertType('string|false', $handler->read('id'));
	assertType('string|false', $interface->read('id'));
};
