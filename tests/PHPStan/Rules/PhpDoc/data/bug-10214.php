<?php

namespace Bug10241;

interface Common {
	/** @phpstan-assert-if-true A $this */
	public function isA(): bool;

	/** @phpstan-assert-if-true B $this */
	public function isB(): bool;
}

trait IsCommon {
	public function isA(): bool {
		return $this instanceof A;
	}

	public function isB(): bool {
		return $this instanceof B;
	}
}

class A implements Common
{
	use IsCommon;


	public function isApple(): bool
	{
		return true;
	}
}

class B implements Common
{
	use IsCommon;

	public function isBanana(): bool
	{
		return true;
	}
}
