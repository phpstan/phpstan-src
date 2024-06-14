<?php

namespace {
	/**
	 * @return NoReturn
	 */
	function f1() {
		throw new \LogicException();
	}

	/**
	 * @return \NoReturn
	 */
	function f2() {
		return new NoReturn;
	}

	\PHPStan\Testing\assertType('never', f1());
	\PHPStan\Testing\assertType('NoReturn', f2());
}

namespace NoReturnDefined {
	class NoReturn {}

	/**
	 * @return NoReturn
	 */
	function f1() {
		return new NoReturn;
	}

	/**
	 * @return \NoReturn
	 */
	function f2() {
		return new \NoReturn;
	}

	\PHPStan\Testing\assertType('NoReturnDefined\\NoReturn', f1());
	\PHPStan\Testing\assertType('NoReturn', f2());
}

namespace NoReturnUndefined {
	/**
	 * @return NoReturn
	 */
	function f1() {
		throw new \LogicException();
	}

	/**
	 * @return NoReturn
	 */
	function f2() {
		throw new \LogicException();
	}

	\PHPStan\Testing\assertType('never', f1());
	\PHPStan\Testing\assertType('never', f2());
}
