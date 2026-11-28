<?php declare(strict_types = 1);

namespace WeakArrayOffsetWrites;

use function PHPStan\Testing\assertType;

class Item
{

	public int $k = 0;

}

/** @param array<int, list<string>> $a */
function appendIntoOneSlot(array $a, int $i, int $other): void
{
	$a[$i][] = 'x';
	// only the slot at $i was written, the others may still be empty
	assertType('non-empty-array<int, list<string>>', $a);
	assertType('non-empty-list<string>', $a[$i]);
	assertType('list<string>', $a[$other]);
}

/** @param array<int, array{foo: non-empty-list<Item>}> $groups */
function addKeyToOneGroup(array $groups, int $k, Item $item): void
{
	$groups[$k]['bar'][] = $item;
	assertType('non-empty-array<int, array{foo: non-empty-list<WeakArrayOffsetWrites\Item>}|array{foo?: non-empty-list<WeakArrayOffsetWrites\Item>, bar: array{WeakArrayOffsetWrites\Item}}>', $groups);
}

/** @param array<int, array{t: 'orig'}> $a */
function sameSlotTwice(array $a, int $i, int $j): void
{
	$a[$i]['t'] = 'i1';
	$a[$i]['t'] = 'i2';
	// the value of the first write is gone
	assertType("non-empty-array<int, array{t: 'i2'}|array{t: 'orig'}>", $a);

	$a[$i]['t'] = 'i3';
	$i++;
	$a[$i]['t'] = 'i4';
	// 'i3' overwrote 'i2', and $i changed, so 'i4' went into another slot
	assertType("non-empty-array<int, array{t: 'i3'}|array{t: 'i4'}|array{t: 'orig'}>", $a);

	$a[$j]['t'] = 'j1';
	$a[rand()]['t'] = 'r';
	$a[$j]['t'] = 'j2';
	// a write to another slot in between stays, and the array written in
	// between no longer tells which slot 'j1' went into
	assertType("non-empty-array<int, array{t: 'i3'}|array{t: 'i4'}|array{t: 'j1'}|array{t: 'j2'}|array{t: 'orig'}|array{t: 'r'}>", $a);
}

/** @param list<Item> $items */
function groupBy(array $items): void
{
	$groups = [];
	foreach ($items as $item) {
		if (!isset($groups[$item->k])) {
			$groups[$item->k] = [];
		}
		$groups[$item->k][] = $item;
	}
	assertType('array<int, non-empty-list<WeakArrayOffsetWrites\Item>>', $groups);
}
