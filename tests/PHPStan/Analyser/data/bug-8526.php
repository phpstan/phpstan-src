<?php

namespace Bug8526;

define('FOO', true);
define('DYNAMICARRAY', []);

function doFoo(): void
{
	if (isset(DYNAMICARRAY['MyKey'])) {
		echo 'has key';
	}
	if (FOO) {
		echo 'foo';
	}
}
