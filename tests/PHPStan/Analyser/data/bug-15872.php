<?php declare(strict_types = 1);

namespace Bug15872;

use function PHPStan\Testing\assertType;

class Builder
{

	public function where(string $condition): self
	{
		return $this;
	}

}

final class Moment
{

	public function minusMonths(int $months): self
	{
		return $this;
	}

}

function fresh(Builder $factory): void
{
	$to = new Moment();
	$byKey = [];
	for ($i = 0; $i < 12; $i++) {
		$qb = $factory->where('a');
		$byKey['k'][] = $i;
		$to = $to->minusMonths(1);
	}
	assertType('Bug15872\Moment', $to);
	assertType('array{k: non-empty-list<int<0, 11>>}', $byKey);
	assertType('12', $i);
}

function reTyped(Builder $qb, bool $b): void
{
	$count = 0;
	$seen = false;
	while ($b) {
		$qb = $qb->where('a');
		$count++;
		$seen = true;
	}
	assertType('int<0, max>', $count);
	assertType('bool', $seen);
	assertType('mixed', $qb);
}

function doWhile(Builder $factory, bool $b): void
{
	$to = new Moment();
	do {
		$qb = $factory->where('a');
		$to = $to->minusMonths(1);
	} while ($b);
	assertType('Bug15872\Moment', $to);
}

/** @param list<int> $items */
function inForeach(Builder $factory, array $items): void
{
	$sum = 0;
	foreach ($items as $item) {
		$qb = $factory->where('a');
		$sum = $item;
	}
	assertType('int', $sum);
}
