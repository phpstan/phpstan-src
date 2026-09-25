<?php declare(strict_types = 1);

namespace TemplateArgumentReplayInvalidation;

use function PHPStan\Testing\assertType;

/** @template T */
class Collection
{

	/** @param T|null $value */
	public function __construct($value = null)
	{
	}

	/** @return T|null */
	public function get(int $key)
	{
		return null;
	}

}

/** @param Collection<string> $collection */
function takeStrings(Collection $collection): void
{
}

function reassignedArgument(): void
{
	$key = rand();
	$collection = new Collection(null);
	if ($collection->get($key) === null) {
		return;
	}
	assertType('string', $collection->get($key));
	if (is_int($collection->get($key))) {
		$key = 1;
	}
	$key = 5;
	assertType('string|null', $collection->get($key));
	takeStrings($collection);
}
