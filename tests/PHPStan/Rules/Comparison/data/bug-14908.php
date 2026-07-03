<?php // lint >= 8.1

declare(strict_types = 1);

namespace BugRule14908;

enum Grade
{
	case One;
	case Two;
	case Three;
}

enum Kind
{
	case K1;
	case K2;
	case K3;
}

class Flags
{
	public bool $flagA = false;
}

function run(Kind $kind, Grade $grade, Flags $flags, bool $extra, bool $cond): void
{
	$forced = false;
	if (
		$grade !== Grade::Three
		&& $cond
		&& in_array($kind, [Kind::K1, Kind::K2], true)
		&& $flags->flagA === true
	) {
		$forced = true;
	}

	if (
		$forced === false
		&& (
			($grade === Grade::One && $extra === false)
			|| ($cond && $grade !== Grade::Three)
		)
	) {
		throw new \Exception();
	}

	if ($grade !== Grade::Three) {
		if ($flags->flagA === false) {
			throw new \Exception();
		}
		if (in_array($kind, [Kind::K1, Kind::K2], true)) {
			echo 'reachable';
		}
	}
}
