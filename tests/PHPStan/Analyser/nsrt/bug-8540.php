<?php declare(strict_types = 1);

namespace Bug8540;

use SplObjectStorage;
use stdClass;
use function PHPStan\Testing\assertType;

function writeThroughArrayAccess(): void
{
	$storage = new SplObjectStorage();
	$storage[new stdClass()] = 'data';
	assertType('SplObjectStorage<stdClass, \'data\'>', $storage);
}

function attach(): void
{
	$storage = new SplObjectStorage();
	$storage->attach(new stdClass(), 'data');
	assertType('SplObjectStorage<stdClass, \'data\'>', $storage);
}

function explicitOffsetSet(): void
{
	$storage = new SplObjectStorage();
	$storage->offsetSet(new stdClass(), 'data');
	assertType('SplObjectStorage<stdClass, \'data\'>', $storage);
}

function severalWrites(): void
{
	$storage = new SplObjectStorage();
	$storage[new stdClass()] = 'data';
	$storage[new \DateTimeImmutable()] = 17;
	assertType('SplObjectStorage<DateTimeImmutable|stdClass, 17|\'data\'>', $storage);
}
