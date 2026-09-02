<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14908Nsrt;

use function PHPStan\Testing\assertType;

enum Grade { case One; case Two; case Three; }
enum Kind { case K1; case K2; case K3; }

class Flags { public bool $flagA = false; }

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

    // Intermediate `if` narrowing ANOTHER value (`$extra === false`) in a disjunction.
    // This is the ingredient that defeats the #14807 fix.
    if (
        $forced === false
        && (
            ($grade === Grade::One && $extra === false)
            || ($cond && $grade !== Grade::Three)
        )
    ) {
        throw new \Exception();
    }

    // The narrowing from the first `if` must not leak here: skipping the first
    // `if` says nothing about $flags->flagA or $kind on their own.
    if ($grade !== Grade::Three) {
        assertType('bool', $flags->flagA);
        assertType('Bug14908Nsrt\Kind', $kind);
    }
}
