<?php declare(strict_types = 1);

namespace Bug15421;

use function PHPStan\Testing\assertType;

final class Day {
	public string $date = 'x';
	public function toDateString(): string { return 'x'; }
	public function next(): ?Day { return null; }
}
final class Holder {
	public ?Day $day = null;
	/** @var array{day?: Day|null} */
	public array $days = [];
	public function getDay(): ?Day { return $this->day; }
}
final class Crate {}

function test(Crate $crate): void
{
	$a = $crate->holder->day?->toDateString(); // reported on 2.3.0
	$h = $crate->holder;
	$b = $h->day?->toDateString();             // not reported
}

function analogous(Crate $crate): void
{
	assertType('Bug15421\Holder', $crate->holder);
	assertType('Bug15421\Day|null', $crate->holder->day);
	assertType('string|null', $crate->holder->day?->toDateString());
	assertType('string|null', $crate->holder->day?->date);
	assertType('Bug15421\Day|null', $crate->holder->day?->next()?->next());
	assertType('string|null', $crate->holder->getDay()?->toDateString());
	assertType('string|null', $crate->holder->getDay()?->date);
	assertType('string|null', $crate->holder->day->date ?? null);
	assertType('string|null', $crate->holder->days['day']?->date);
	assertType('bool', isset($crate->holder->day->date));
	assertType('bool', empty($crate->holder->day->date));
	if ($crate->holder->day !== null) {
		assertType('Bug15421\Day', $crate->holder->day);
		assertType('string', $crate->holder->day->toDateString());
	}
}
