<?php declare(strict_types=1);

namespace App;

use Redeclare\Builder\gadget;

class Probe
{

	// Probing a name that is only a function forces a hasClass() lookup, which used to re-run the
	// custom autoloader and fatally re-include the already-loaded function file.
	public function isThing(object $o): bool
	{
		return $o instanceof \Redeclare\Builder\thing;
	}

	// A class and a function may share a name in PHP. Even though the function is already loaded,
	// the class defined in a separate file must still resolve (via the later source locators).
	public function makeGadget(): int
	{
		return (new gadget())->size;
	}

}
