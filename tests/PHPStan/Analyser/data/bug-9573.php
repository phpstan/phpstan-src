<?php declare(strict_types = 1);

namespace Bug9573;

class MyClass {

	/** @var list<positive-int> */
	public array $array;

	/**
	 * @param positive-int $count
	 */
	public function __construct(int $count) {
		$this->array = range(1, $count);
	}

}
