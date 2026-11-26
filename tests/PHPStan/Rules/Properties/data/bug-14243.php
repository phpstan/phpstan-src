<?php // lint >= 8.2

namespace Bug14243;

use ArrayAccess;

class ReadonlyArrayProperties
{

	/** @var array<string, int> */
	public readonly array $params;

	/** @var array<string, array<string, int>> */
	public readonly array $nested;

	/** @param ArrayAccess<string, int> $collection */
	public function __construct(public readonly array $promoted, public readonly ArrayAccess $collection)
	{
		$a = &$this->promoted['x'];
		$this->params = ['x' => 1];
		$this->nested = ['a' => ['b' => 1]];
	}

	public function assignElementsByReference(): void
	{
		$a = &$this->params;
		$b = &$this->params['x'];
		$c = &$this->nested['a']['b'];
		$d = &$this->params[];
		$e = &$this->collection['x'];
		$f = &$this->collection;
	}

}

readonly class ReadonlyClass
{

	/** @param array<string, int> $params */
	public function __construct(public array $params)
	{
	}

	public function assignElementByReference(): void
	{
		$a = &$this->params['x'];
	}

}

class OutsideDeclaringClass
{

	public function assignElementByReference(ReadonlyArrayProperties $properties): void
	{
		$a = &$properties->params['x'];
	}

}

class ElementTargets
{

	/** @var array<string, int>|ArrayAccess<string, int> */
	public readonly array|ArrayAccess $union;

	/** @var array<string, ArrayAccess<string, int>> */
	public readonly array $roArray;

	/**
	 * @param array<string, int>|ArrayAccess<string, int> $union
	 * @param array<string, ArrayAccess<string, int>> $roArray
	 */
	public function __construct(array|ArrayAccess $union, array $roArray)
	{
		$this->union = $union;
		$this->roArray = $roArray;
	}

	/** @return array<string, int> */
	public function arrayFromCall(): array
	{
		return ['x' => 1];
	}

	/** @param array<string, int> $local */
	public function assignElementsByReference(array $local): void
	{
		$a = &$this->arrayFromCall()['x'];
		$b = &$local['x'];
		$c = &$this->union['x'];
		$d = &$this->roArray['a']['b'];
	}

}
