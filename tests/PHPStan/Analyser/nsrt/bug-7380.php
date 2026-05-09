<?php declare(strict_types = 1);

namespace Bug7380;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type Attrs array{foo?: string, bar?: 5|6|7, baz?: bool}
 */
final class Foo {

	/**
	 * @template K of key-of<Attrs>
	 * @param K $key
	 * @param Attrs[K] $val
	 */
	public function setAttribute(string $key, mixed $val): void
	{
		$attr = $this->getAttributes();
		$attr[$key] = $val;
		assertType('array{foo?: string, bar?: 5|6|7, baz?: bool}', $attr);
		$this->setAttributes($attr);
	}

	/** @return Attrs */
	public function getAttributes(): array
	{
		return [];
	}

	/** @param Attrs $attr */
	public function setAttributes(array $attr): void
	{
	}
}

/**
 * @template T of array<string, mixed>
 */
final class GenericBar {

	/**
	 * @template K of key-of<T>
	 * @param K $key
	 * @param T[K] $val
	 */
	public function setAttribute(string $key, mixed $val): void
	{
		$attr = $this->getAttributes();
		$attr[$key] = $val;
		$this->setAttributes($attr);
	}

	/** @return T */
	public function getAttributes(): array
	{
		return [];
	}

	/** @param T $attr */
	public function setAttributes(array $attr): void
	{
	}
}
