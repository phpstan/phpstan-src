<?php

namespace Bug5607;

class A {}

class Cl
{
	/** @var A|null */
	public $u = [A::class, 'ui' => 'basic segment'];

	/**
	 * @param array<int|string, class-string|string> $x
	 */
	public function mm($x): void
	{
		throw new \Exception();
	}
}
