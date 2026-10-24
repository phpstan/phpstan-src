<?php // lint >= 8.4

namespace Bug14243Asymmetric;

use ArrayAccess;

class AsymmetricVisibility
{

	/** @var array<string, int> */
	public private(set) array $params = ['x' => 1];

	/** @var array<string, array<string, int>> */
	public protected(set) array $nested = ['a' => ['b' => 1]];

	/** @param ArrayAccess<string, int> $collection */
	public function __construct(public private(set) ArrayAccess $collection)
	{
	}

}

class OutsideDeclaringClass
{

	public function assignElementsByReference(AsymmetricVisibility $properties): void
	{
		$a = &$properties->params['x'];
		$b = &$properties->nested['a']['b'];
		$c = &$properties->collection['x'];
	}

}
