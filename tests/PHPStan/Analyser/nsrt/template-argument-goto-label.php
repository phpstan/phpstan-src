<?php declare(strict_types = 1);

namespace TemplateArgumentGotoLabel;

use function PHPStan\Testing\assertType;

/** @template T */
class Collection
{

	/** @param list<T> $items */
	public function __construct(array $items)
	{
	}

}

/** @param Collection<int> $collection */
function takeInts(Collection $collection): void
{
}

function gotoBeforeSite(bool $b): int
{
	$x = 1;
	if ($b) {
		$x = 2;
		goto end;
	}
	$collection = new Collection([1]);
	takeInts($collection);
	assertType('TemplateArgumentGotoLabel\Collection<int>', $collection);
	$x = 3;
	end:
	assertType('2|3', $x);
	return $x;
}
