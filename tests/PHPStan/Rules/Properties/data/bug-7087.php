<?php // lint >= 8.1

namespace Bug7087;

class Foo {
	/**
	 * @var array<int<0,max>, mixed> $array1
	 */
	public readonly array $array1;
	/**
	 * @var array<int<0,max>, mixed> $array2
	 */
	public readonly array $array2;

	/**
	 * @param array<int<0,max>, mixed> $param
	 */
	public function __construct(array $param) {
		$this->array1 = $this->foo($param);
		$this->array2 = $this->bar($param);
	}

	/**
	 * @param array<int<0,max>, mixed> $param
	 * @return array<int<0,max>, mixed>
	 */
	private function foo(array $param): array {
		return $param;
	}

	/**
	 * @template IKey
	 * @template IValue
	 * @param array<IKey, IValue> $param
	 * @return array<IKey, IValue>
	 */
	private function bar(array $param): array {
		return $param;
	}
}
