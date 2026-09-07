<?php // lint >= 8.0
declare(strict_types = 1);

namespace TemplateArgumentBounds;

use SplObjectStorage;
use stdClass;
use function PHPStan\Testing\assertType;

/** @template T of object */
class Collection
{

	/** @param T $item */
	public function add($item): void
	{
	}

}

function invalidOffset(): void
{
	$storage = new SplObjectStorage();
	$storage[[1, 2, 3]] = 'test';
	assertType("SplObjectStorage<object, 'test'>", $storage);
}

function invalidArgument(): void
{
	$collection = new Collection();
	$collection->add('invalid');
	assertType('TemplateArgumentBounds\Collection<object>', $collection);
}

function partiallyValidArgument(stdClass|string $item): void
{
	$collection = new Collection();
	$collection->add($item);
	assertType('TemplateArgumentBounds\Collection<stdClass>', $collection);
}

function validArgumentAfterInvalidArgument(stdClass $item): void
{
	$collection = new Collection();
	$collection->add('invalid');
	$collection->add($item);
	assertType('TemplateArgumentBounds\Collection<stdClass>', $collection);
}
