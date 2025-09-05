<?php

namespace Bug13384c;

function doFoo(): bool {
	return false;
}

function doFoo2(): bool {
	return true;
}

function doFoo3(): bool {
	if (rand(0, 1)) {
		return true;
	}
	return false;
}


class Bug13384c {
	public function doBarPublic(): bool {
		return false;
	}

	/**
	 * @return false
	 */
	private function doBarPhpdocReturn(): bool {
		return false;
	}

	private function doBar(): bool {
		return false;
	}

	private function doBar2(): bool {
		return true;
	}

	private function doBar3(): bool {
		if (rand(0, 1)) {
			return true;
		}
		return false;
	}

	private function doBarMixed() {
		return true;
	}

	/**
	 * @return bool
	 */
	private function doBarPhpdoc() {
		return true;
	}

}

class Bug13384Static {
	private static function doBar(): bool {
		return false;
	}

	private static function doBar2(): bool {
		return true;
	}

	private static function doBar3(): bool {
		if (rand(0, 1)) {
			return true;
		}
		return false;
	}

	private static function doBarMixed() {
		return true;
	}

	/**
	 * @return bool
	 */
	private static function doBarPhpdoc() {
		return true;
	}

}

/**
 * @return bool
 */
function doFooPhpdoc() {
	return true;
}

/**
 * @return bool
 */
function doFooPhpdoc2() {
	return false;
}

function doFooMixed() {
	return true;
}
