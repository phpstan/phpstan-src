<?php // lint >= 8.0

namespace Bug6118;


/**
 * @template-covariant T of mixed
 */
class Element {
	/** @var T */
	public mixed $value;

	/**
	 * @param Element<mixed> $element
	 */
	function getValue(Element $element) : void {
	}

	/**
	 * @param Element<int> $element
	 */
	function takesValue(Element $element) : void {
		$this->getValue($element);
	}


	/**
	 * @param Element<int|null> $element
	 */
	function getValue2(Element $element) : void {
	}

	/**
	 * @param Element<int> $element
	 */
	function takesValue2(Element $element) : void {
		getValue2($element);
	}
}

/**
 * @template-covariant T of string|int|array<mixed>
 */
interface Example {
	/**
	 * @return T|null
	 */
	public function normalize(): string|int|array|null;
}

/**
 * @implements Example<string|int|array<mixed>>
 */
class Foo implements Example {
	public function normalize(): int
	{
		return 0;
	}
	/**
	 * @param Example<int|string|array<mixed>> $example
	 */
	function foo(Example $example): void {
	}

	function bar(): void {
		$this->foo(new Foo());
	}
}
