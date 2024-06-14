<?php

namespace AssertClassType;

use Exception;
use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class HelloWorld {
	/** @var T */
	private $value;

	/** @param T $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * @template K
	 * @param K $data
	 * @phpstan-assert T $data
	 */
	public function assert($data): void
	{
		if ($data !== $this->value) {
			throw new Exception();
		}
	}
}

function () {
	$a = new HelloWorld(123);
	assertType('AssertClassType\\HelloWorld<int>', $a);

	$b = $_GET['value'];
	$a->assert($b);

	assertType('int', $b);
};
