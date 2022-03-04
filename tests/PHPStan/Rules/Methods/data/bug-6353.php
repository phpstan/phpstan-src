<?php

namespace Bug6353;


/**
 * @template-covariant T
 */
interface Option {
	/** @return T */
	public function get();
}

class Foo
{

	/**
	 * @template T
	 * @param T $t
	 * @return Option<T>
	 */
	function some($t): Option {
		/** @implements Option<T> */
		return new class($t) implements Option {
			/**
			 * @param T $t
			 */
			public function __construct(private $t) {
			}

			/**
			 * @return T
			 */
			public function get() {
				return $this->t;
			}
		};
	}

	/**
	 * @return Option<never>
	 */
	function none(): Option {
		/** @implements Option<never> */
		return new class() implements Option {

			/**
			 * @return never
			 */
			public function get() {
				throw new \Exception();
			}
		};
	}
	/**
	 * @template T
	 * @param callable():T $fn
	 * @return Option<T>
	 */
	function fromCallbackThatCanThrow(callable $fn) {
		try {
			$a = $this->some($fn());
		} catch (\Throwable $failure) {
			$a = $this->none();
		}
		return $a;
	}

}
