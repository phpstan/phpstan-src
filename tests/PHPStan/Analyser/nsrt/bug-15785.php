<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15785;

use stdClass;
use function PHPStan\Testing\assertType;

final class Entry
{
	public function __construct(public ?stdClass $result) {}
}

final class Cache
{
	public function __construct(public Entry $selected) {}

	public function current(): Entry
	{
		return $this->selected;
	}
}

function run(Cache $cache): void
{
	$result = $cache->current()->result;
	if ($result !== null) {
		return;
	}

	assertType('null', $cache->current()->result);
	$cache->selected = new Entry(new stdClass());
	assertType('stdClass|null', $cache->current()->result);

	$result = $cache->current()->result;
	assertType('stdClass|null', $result);
}

function remembersThroughAssignedVariable(Cache $cache): void
{
	$result = $cache->current()->result;
	$cache->selected = new Entry(new stdClass());
	if ($result === null) {
		assertType('stdClass|null', $cache->current()->result);
	}
}
