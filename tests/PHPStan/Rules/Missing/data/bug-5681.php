<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug5681;

use Exception;
use Generator;

function doSomething(): mixed
{
	return 1;
}

function (bool $condition): Generator {
	$condition = $condition
		? yield doSomething()
		: false;

	if (!$condition) {
		throw new Exception();
	}
};

function (bool $condition): Generator {
	$x = $condition
		? false
		: yield doSomething();

	if (!$x) {
		throw new Exception();
	}
};

function (bool $condition): Generator {
	$x = $condition ?: yield doSomething();

	if (!$x) {
		throw new Exception();
	}
};

function (bool $condition): Generator {
	if ($condition) {
		throw yield 1;
	}
};
