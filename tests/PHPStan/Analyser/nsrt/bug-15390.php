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

function allCases(Status $status): void
{
	foreach ([Status::A, Status::B, Status::C] as $s) {
		if ($status === $s) {
			return;
		}
	}

	assertType('*NEVER*', $status);
}

function someCases(Status $status): void
{
	foreach ([Status::A, Status::B] as $s) {
		if ($status === $s) {
			return;
		}
	}

	assertType('Bug15390\Status::C', $status);
}

function keyed(Status $status): int
{
	foreach ([1 => Status::A, 2 => Status::B, 3 => Status::C] as $value => $s) {
		if ($status === $s) {
			return $value;
		}
	}

	assertType('*NEVER*', $status);

	return 0;
}

function inArray(Status $status): int
{
	foreach ([1 => [Status::A, Status::B], 2 => [Status::C]] as $value => $statuses) {
		if (in_array($status, $statuses, true)) {
			return $value;
		}
	}

	assertType('*NEVER*', $status);

	return 0;
}

/**
 * @param 1|2|3 $i
 */
function integers(int $i): void
{
	foreach ([1, 2, 3] as $s) {
		if ($i === $s) {
			return;
		}
	}

	assertType('*NEVER*', $i);
}

function withBreak(Status $status): void
{
	foreach ([Status::A, Status::B, Status::C] as $s) {
		if ($status === Status::B) {
			break;
		}
		if ($status === $s) {
			return;
		}
	}

	assertType('Bug15390\Status::B', $status);
}
