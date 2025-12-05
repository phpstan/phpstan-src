<?php // lint >= 8.0

namespace Bug13384c;

function doFoo(): bool {
	return returnsFalse();
}

function doFoo2(): bool {
	return returnsTrue();
}

function doFoo3(): bool {
	if (rand(0, 1)) {
		return true;
	}
	return false;
}


class Bug13384c {
	public function doBarPublic(): bool {
		return returnsFalse();
	}

	/**
	 * @return false
	 */
	private function doBarPhpdocReturn(): bool {
		return returnsFalse();
	}

	private function doBar(): bool {
		return returnsFalse();
	}

	private function doBar2(): bool {
		return returnsTrue();
	}

	private function doBar3(): bool {
		if (rand(0, 1)) {
			return true;
		}
		return false;
	}

	private function doBarMixed() {
		return returnsTrue();
	}

	/**
	 * @return bool
	 */
	private function doBarPhpdoc() {
		return returnsTrue();
	}

}

class Bug13384Static {
	private static function doBar(): bool {
		return returnsFalse();
	}

	private static function doBar2(): bool {
		return returnsTrue();
	}

	private static function doBar3(): bool {
		if (rand(0, 1)) {
			return true;
		}
		return false;
	}

	private static function doBarMixed() {
		return returnsTrue();
	}

	/**
	 * @return bool
	 */
	private static function doBarPhpdoc() {
		return returnsTrue();
	}

}

/**
 * @return bool
 */
function doFooPhpdoc() {
	return returnsTrue();
}

/**
 * @return bool
 */
function doFooPhpdoc2() {
	return returnsFalse();
}

function doFooMixed() {
	return returnsTrue();
}

/**
 * @return true
 */
function returnsTrue(): bool {
	return true;
}

/**
 * @return false
 */
function returnsFalse(): bool {
	return false;
}

function returnsTrueNoPhpdoc(): bool {
	return true;
}

function returnsFalseNoPhpdoc(): bool {
	return false;
}

function returnsTrueUnionReturn(): int|bool {
	return true;
}

/**
 * @return int|bool
 */
function returnsTruePhpdocUnionReturn() {
	return true;
}
