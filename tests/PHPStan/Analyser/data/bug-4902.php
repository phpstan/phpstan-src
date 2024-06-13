<?php // onlyif PHP_VERSION_ID < 80000

namespace Bug4902;

use function PHPStan\Testing\assertType;

/**
 * @template T-wrapper
 */
class Wrapper {
	/** @var T-wrapper */
	public $value;

	/**
	 * @param T-wrapper $value
	 */
	public function __construct($value) {
		$this->value = $value;
	}

	/**
	 * @template T-unwrap
	 * @param Wrapper<T-unwrap> $wrapper
	 * @return T-unwrap
	 */
	function unwrap(Wrapper $wrapper) {
		return $wrapper->value;
	}

	/**
	 * @template T-wrap
	 * @param T-wrap $value
	 *
	 * @return Wrapper<T-wrap>
	 */
	function wrap($value): Wrapper
	{
		return new Wrapper($value);
	}


	/**
	 * @template T-all
	 * @param Wrapper<T-all> ...$wrappers
	 */
	function unwrapAllAndWrapAgain(Wrapper ...$wrappers): void {
		assertType('list<T-all (method Bug4902\Wrapper::unwrapAllAndWrapAgain(), argument)>', array_map(function (Wrapper $item) {
			return $this->unwrap($item);
		}, $wrappers));
		assertType('list<T-all (method Bug4902\Wrapper::unwrapAllAndWrapAgain(), argument)>', array_map(fn (Wrapper $item) => $this->unwrap($item), $wrappers));
	}

}
