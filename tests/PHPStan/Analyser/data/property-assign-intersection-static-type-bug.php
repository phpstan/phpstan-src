<?php

namespace PropertyAssignIntersectionStaticTypeBug;

abstract class Base
{
	/** @var string */
	private $foo;

	public function __construct(string $foo)
	{
		\assert($this instanceof Frontend || $this instanceof Backend);

		$this->foo = $foo;
	}
}

class Frontend extends Base
{

}

class Backend extends Base
{

}
