<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15390;

use function PHPStan\Testing\assertType;

enum Status
{
	case A;
	case B;
	case C;
}

function f(Status $status): void
{
	foreach ([Status::A, Status::B, Status::C] as $s) {
		if ($status === $s) {
			return;
		}
	}

	assertType('*NEVER*', $status);
}

function g(Status $status): int
{
	foreach ([1 => [Status::A, Status::B], 2 => [Status::C]] as $value => $statuses) {
		if (in_array($status, $statuses, true)) {
			return $value;
		}
	}

	assertType('*NEVER*', $status);

	return 0;
}

function h(Status $status): void
{
	foreach ([Status::A, Status::B] as $s) {
		if ($status === $s) {
			return;
		}
	}

	assertType('Bug15390\Status::C', $status);
}

/** @param 1|2|3 $i */
function i(int $i): void
{
	foreach ([1, 2, 3] as $v) {
		if ($i === $v) {
			throw new \Exception();
		}
	}

	assertType('*NEVER*', $i);
}
