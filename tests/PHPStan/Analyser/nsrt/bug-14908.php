<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14908;

use function PHPStan\Testing\assertType;

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

	// Intermediate `if` narrowing ANOTHER value (`$extra === false`) inside a
	// disjunction. The disjunction's truth is not captured by narrowing `$grade`
	// alone, so the boolean-decomposition holder `$grade ⇒ $forced` must not be
	// created — otherwise the narrowings from the first `if` leak into the block
	// below.
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
		assertType('bool', $flags->flagA);
		assertType('Bug14908\\Kind', $kind);
		assertType('bool', $forced);
	}
}

// The narrowing must still fire when the guard genuinely selects the branch:
// `$forced === true` really does imply the first `if` was taken.
function soundNarrowing(Kind $kind, Grade $grade, Flags $flags, bool $cond): void
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

	if ($forced === false) {
		throw new \Exception();
	}

	assertType('true', $flags->flagA);
	assertType('Bug14908\\Kind::K1|Bug14908\\Kind::K2', $kind);
}
