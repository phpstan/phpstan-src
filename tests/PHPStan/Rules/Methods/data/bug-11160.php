<?php

declare(strict_types=1);

namespace Bug11160;

/**
 * @template T
 * @phpstan-type MyCustomType = string
 */
trait MyTrait
{
	/**
	 * @return MyCustomType
	 */
	public function foo()
	{
		return 'hi';
	}
}

class MyParent
{
	/**
	 * @use MyTrait<string>
	 */
	use MyTrait;
}

/**
 * @phpstan-import-type MyCustomType from MyTrait
 */
class MyChild
{

	/**
	 * @return MyCustomType
	 */
	function bar()
	{
		return $this->foo();
	}
}
