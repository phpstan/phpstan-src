<?php

namespace Bug3632;

class NiceClass
{

}

function (): string {
	$a = null;
	$b = null;

	if(rand(2,10) == 4) {
		$a = new NiceClass();
	}

	if(rand(2,10) == 8) {
		$b = new NiceClass();
	}

	if (!$a instanceof NiceClass && !$b instanceof NiceClass) {
		// none have been set, ignore
		return null;
	}

	if ($a instanceof NiceClass && $b instanceof NiceClass) {
		// both have been set, ignore
		return null;
	}

	if ($a instanceof NiceClass && !$b instanceof NiceClass) {
		return 'A has been added';
	}

	if (!$a instanceof NiceClass && $b instanceof NiceClass) {
		return 'A has been removed';
	}

	return 'Foo';
};
