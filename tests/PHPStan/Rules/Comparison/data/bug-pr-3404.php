<?php // lint >= 8.0

namespace BugPR3404;

interface Location
{

}

/** @return class-string<Location> */
function aaa(): string
{

}

function (Location $l): void {
	if (is_a($l, aaa(), true)) {
		// might not always be true. $l might be one subtype of Location, aaa() might return a name of a different subtype of Location
	}

	if (is_a($l, Location::class, true)) {
		// always true
	}
};
